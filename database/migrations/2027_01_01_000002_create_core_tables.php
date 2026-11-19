<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('admin_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('title', 30);
            $t->timestamps();
        });

        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('filename')->unique();
            $t->string('original_name');
            $t->string('mime', 60);
            $t->unsignedBigInteger('size');
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('alt')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('impersonator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 60)->index();
            $t->string('subject_type', 60)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('old')->nullable();
            $t->json('new')->nullable();
            $t->string('ip', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('impersonations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id')->constrained('users');
            $t->foreignId('member_id')->constrained('users');
            $t->string('ip', 45)->nullable();
            $t->timestamp('started_at');
            $t->timestamp('expires_at');
            $t->timestamp('ended_at')->nullable();
            $t->string('ended_reason', 20)->nullable();
            $t->timestamps();
        });

        // Invite codes: the only way in. The code is stored hashed; code_hint is the last four characters.
        Schema::create('invites', function (Blueprint $t) {
            $t->id();
            $t->string('code_lookup', 64)->unique();   // sha256 of the code, for finding the row
            $t->string('code_hint', 4);
            $t->string('kind', 10)->default('member'); // member | admin
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('label')->nullable();
            $t->string('status', 10)->default('active'); // active | claimed | revoked
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('claimed_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedSmallInteger('wrong_tries')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('invite_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invite_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('reservation_id')->nullable();
            $t->string('type', 30)->index();
            $t->string('ip', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('agreement_versions', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->longText('body');
            $t->timestamp('published_at');
            $t->timestamps();
        });

        Schema::create('reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invite_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reference', 24)->unique();
            $t->string('status', 30)->default('agreed')->index();
            $t->unsignedBigInteger('price_cents');
            $t->unsignedBigInteger('deposit_cents');
            $t->unsignedBigInteger('balance_cents');
            $t->unsignedInteger('agreement_version');
            $t->timestamp('agreed_at');
            $t->string('agreed_ip', 45)->nullable();
            $t->timestamp('deposit_verified_at')->nullable();
            $t->timestamp('balance_due_at')->nullable();
            $t->timestamp('balance_verified_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 10);                  // deposit | balance
            $t->string('number', 30)->unique();
            $t->unsignedBigInteger('amount_cents');
            $t->timestamp('issued_at');
            $t->timestamp('due_at')->nullable();
            $t->timestamp('emailed_at')->nullable();
            $t->string('status', 12)->default('open'); // open | paid | void
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('method', 10)->default('wire');
            $t->unsignedBigInteger('amount_cents');
            $t->string('status', 12)->default('pending'); // pending | verified | rejected
            $t->string('proof_path')->nullable();
            $t->string('proof_name')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 10);                  // day7 | day9
            $t->timestamp('sent_at');
            $t->unique(['reservation_id', 'kind']);
        });

        // Editable vehicle story: one row per page section, with an image and an optional video.
        Schema::create('page_sections', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('title');
            $t->string('summary', 400)->nullable();
            $t->longText('body')->nullable();
            $t->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('video_path')->nullable();       // private upload, served through the app
            $t->string('video_url', 500)->nullable();   // or a hosted video
            $t->foreignId('poster_id')->nullable()->constrained('media')->nullOnDelete();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['page_sections', 'reminders', 'payments', 'invoices', 'reservations', 'agreement_versions', 'invite_events', 'invites', 'impersonations', 'audit_logs', 'media', 'admin_profiles', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
