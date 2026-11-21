<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Set up the platform</title>
<style>
:root{--gold:#d7a970;--ink:#e8e8ea;--muted:#9a9ba0;--line:#2c2c30;--bg:#0e0e10;--card:#151518;--bad:#e5766b;--ok:#6fcf97}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:17px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.top{padding:1rem 1.25rem;border-bottom:1px solid var(--line)}
.top b{font-weight:600;letter-spacing:.12em;text-transform:uppercase;font-size:.95rem}
.top span{color:var(--muted);margin-left:.75rem;font-size:.95rem}
.wrap{max-width:940px;margin:0 auto;padding:1.5rem 1.25rem 3rem;display:grid;grid-template-columns:220px 1fr;gap:2rem}
.steps{list-style:none;margin:0;padding:0;counter-reset:s}
.steps li{counter-increment:s;padding:.45rem 0;color:var(--muted);display:flex;gap:.6rem;align-items:baseline}
.steps li::before{content:counter(s);display:inline-grid;place-items:center;width:1.6rem;height:1.6rem;border:1px solid var(--line);border-radius:4px;font-size:.85rem;flex:none;background:var(--card)}
.steps li.on{color:var(--ink);font-weight:600}
.steps li.on::before{background:var(--gold);border-color:var(--gold);color:#111}
.steps li.ok::before{content:"\2713";color:var(--ok);border-color:var(--ok)}
.card{background:var(--card);border:1px solid var(--line);border-radius:4px;padding:1.5rem}
h1{font-weight:300;font-size:1.7rem;line-height:1.2;margin:0 0 .5rem}
p.lead{color:var(--muted);margin:0 0 1.25rem}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.grid .wide{grid-column:1/-1}
label{display:block;font-weight:600;font-size:.95rem}
label small{display:block;font-weight:400;color:var(--muted);margin-top:.2rem}
input:not([type=radio]):not([type=checkbox]),select{width:100%;margin-top:.3rem;padding:.6rem .7rem;font:inherit;border:1px solid #3a3a40;border-radius:4px;background:#0e0e10;color:var(--ink)}
input:focus,select:focus{outline:2px solid var(--gold);outline-offset:1px}
.err{color:var(--bad);font-weight:400;font-size:.92rem;display:block;margin-top:.25rem}
.alert{border:1px solid var(--bad);color:var(--bad);border-radius:4px;padding:.7rem .9rem;margin-bottom:1rem}
.choice{display:flex;gap:.7rem;align-items:flex-start;border:1px solid var(--line);border-radius:4px;padding:.8rem;margin-bottom:.6rem;font-weight:400}
.choice input{margin-top:.35rem;accent-color:var(--gold)}.choice b{display:block}
.actions{margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap}
.btn{font:600 .95rem system-ui,sans-serif;letter-spacing:.06em;text-transform:uppercase;background:var(--gold);color:#111;border:1px solid var(--gold);border-radius:4px;padding:.75rem 1.4rem;cursor:pointer;text-decoration:none}
.btn:hover{filter:brightness(1.08)}.btn:disabled{opacity:.5;cursor:default}
.btn.ghost{background:transparent;color:var(--ink);border-color:#3a3a40}
.rows{list-style:none;padding:0;margin:0}.rows li{display:flex;justify-content:space-between;gap:1rem;padding:.5rem 0;border-bottom:1px solid var(--line)}
.rows .good{color:var(--ok);font-weight:600}.rows .bad{color:var(--bad);font-weight:600}.rows .warn{color:#e0b84f;font-weight:600}
code,.keybox{font-family:ui-monospace,Consolas,monospace;background:#0e0e10;border:1px solid var(--line);padding:.1rem .35rem;border-radius:4px}
.keybox{display:block;padding:.8rem;margin:.5rem 0 1rem}
.code{font-size:1.6rem;letter-spacing:.08em;text-align:center;padding:1.1rem;color:var(--gold)}
.muted{color:var(--muted)}
@media(max-width:760px){.wrap{grid-template-columns:1fr;gap:1rem}.steps{display:flex;flex-wrap:wrap;gap:.25rem 1rem}.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="top"><b>Platform setup</b><span>One time only. Takes about five minutes.</span></div>
<div class="wrap">
@yield('body')
</div>
</body>
</html>
