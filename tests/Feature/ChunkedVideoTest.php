<?php

use App\Models\PageSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('migrate:fresh');
    Storage::fake('local');
    PageSection::ensureDefaults();
    $this->section = PageSection::where('slug', 'showcase')->first();
    $this->admin = videoAdmin();
});

function videoAdmin(): User
{
    $u = User::create(['name' => 'Root', 'email' => 'r'.uniqid().'@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'admin', 'email_verified_at' => now()]);
    \App\Services\StaffDirectory::assign($u, 'Super Admin');

    return $u;
}

/** A file that really is an MP4 to the type check: an ftyp box and then filler. */
function fakeMp4(int $size): string
{
    return "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat('x', $size - 24);
}

function sendPiece($t, string $id, int $offset, string $bytes)
{
    $f = tempnam(sys_get_temp_dir(), 'pc');
    file_put_contents($f, $bytes);

    return $t->post('/admin/content/showcase/video/chunk', ['id' => $id, 'offset' => $offset, 'chunk' => new UploadedFile($f, 'blob', null, null, true)], ['Accept' => 'application/json']);
}

it('joins a video sent in pieces, in any order, and attaches it to the section', function () {
    $data = fakeMp4(3000);
    $id = $this->actingAs($this->admin)->postJson('/admin/content/showcase/video/begin', ['size' => 3000, 'name' => 'tour.mp4'])->assertOk()->json('id');
    expect($id)->toMatch('/^[a-f0-9]{32}$/');

    sendPiece($this, $id, 2000, substr($data, 2000))->assertOk();
    sendPiece($this, $id, 0, substr($data, 0, 1000))->assertOk();
    sendPiece($this, $id, 1000, substr($data, 1000, 1000))->assertOk();
    sendPiece($this, $id, 1000, substr($data, 1000, 1000))->assertOk(); // the same piece sent again is harmless

    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertOk()->assertJson(['ok' => true]);
    $s = $this->section->fresh();
    expect($s->video_path)->toStartWith('private/videos/')->and($s->hasVideo())->toBeTrue();
    expect(Storage::disk('local')->get($s->video_path))->toBe($data);
    expect(glob(Storage::disk('local')->path('private/video-uploads').'/*'))->toBe([]);
    expect(\App\Models\AuditLog::where('action', 'content.updated')->count())->toBe(1);

    // a second upload replaces the first file
    $old = $s->video_path;
    $id = $this->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'b.mp4'])->json('id');
    sendPiece($this, $id, 0, fakeMp4(100))->assertOk();
    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertOk();
    Storage::disk('local')->assertMissing($old);
});

it('says where to resume when a piece is missing', function () {
    $data = fakeMp4(3000);
    $id = $this->actingAs($this->admin)->postJson('/admin/content/showcase/video/begin', ['size' => 3000, 'name' => 'tour.mp4'])->json('id');
    sendPiece($this, $id, 0, substr($data, 0, 1000))->assertOk();
    sendPiece($this, $id, 2000, substr($data, 2000))->assertOk();
    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertStatus(409)->assertJson(['code' => 'incomplete', 'next' => 1000]);
    expect($this->section->fresh()->video_path)->toBeNull();
    sendPiece($this, $id, 1000, substr($data, 1000, 1000))->assertOk();
    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertOk();
});

it('refuses anything that is not an MP4 or WebM, too big a file, and uploads that are not yours', function () {
    $this->actingAs($this->admin);
    $this->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'a.exe'])->assertStatus(422);
    $this->postJson('/admin/content/showcase/video/begin', ['size' => 251 * 1024 * 1024, 'name' => 'a.mp4'])->assertStatus(422)->assertJsonFragment(['error' => 'The video must be 250 MB or smaller.']);

    $id = $this->postJson('/admin/content/showcase/video/begin', ['size' => 55, 'name' => 'a.mp4'])->json('id');
    sendPiece($this, $id, 0, str_repeat('not a video', 5))->assertOk();
    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertStatus(422)->assertJsonFragment(['error' => 'Use an MP4 or WebM video.']);
    expect($this->section->fresh()->video_path)->toBeNull();

    // another admin cannot add to or finish this upload
    $id = $this->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'a.mp4'])->json('id');
    $other = videoAdmin();
    $this->actingAs($other);
    sendPiece($this, $id, 0, fakeMp4(100))->assertStatus(422);
    $this->postJson('/admin/content/showcase/video/finish', ['id' => $id])->assertStatus(422);
    // a piece that runs past the declared size
    $this->actingAs($this->admin);
    sendPiece($this, $id, 90, fakeMp4(100))->assertStatus(422);
});

it('keeps the video upload away from members and visitors', function () {
    $m = User::create(['name' => 'Fay', 'email' => 'fay@example.com', 'password' => 'Sup3rsecret!!', 'role' => 'member', 'member_number' => 'PA-000002']);
    // a stranger gets the same 404 whatever the request asks for, so the address cannot be told from one that does not exist
    $this->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'a.mp4'])->assertNotFound();
    $this->getJson('/admin/content/showcase/video/begin')->assertNotFound();
    $this->getJson('/dashboard')->assertNotFound();
    $this->postJson('/admin/nothing-here')->assertNotFound();
    $this->actingAs($m)->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'a.mp4'])->assertForbidden();
});

it('shows staff the real cause when the server fails, instead of a bare Server Error', function () {
    // a plain file where the upload folder should be makes the folder impossible to create
    Storage::disk('local')->put('private/video-uploads', 'in the way');
    $r = $this->actingAs($this->admin)->postJson('/admin/content/showcase/video/begin', ['size' => 100, 'name' => 'a.mp4']);
    $r->assertStatus(500)->assertJson(['code' => 'server']);
    expect($r->json('error'))->toStartWith('The server could not handle the upload (');
});
