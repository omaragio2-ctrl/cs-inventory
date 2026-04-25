<?php
/*
 * JailBreak Drugs Inventory - MOTD Page
 * Called from AMXX with: ?player=STEAMID&steam=1|0
 * Reads nvault data files directly
 */

$player   = isset($_GET['player']) ? trim($_GET['player']) : '';
$isSteam  = isset($_GET['steam'])  ? intval($_GET['steam']) : 0;

// ── nvault reader ────────────────────────────────────────────────────────────
// nvault stores data in addons/amxmodx/data/vault/drugs_data.vault
// Each line: timestamp\tkey\tvalue
// Key format: STEAMID_d0 .. d3 (drugs), STEAMID_m0 .. m3 (materials), STEAMID_addict

$vaultPath = __DIR__ . '/drugs_data.vault'; // place vault file next to this PHP file
// or adjust path: e.g. '../amxmodx/data/vault/drugs_data.vault'

$drugNames = ['Methamphetamine', 'Amphetamine', 'Krakadzili', 'Heroine'];
$mtNames   = ['Nemsi', 'Kovzi', 'Santebela', 'Qimiuri Narevi'];
$drugIcons = ['💊', '💉', '🧪', '🩸'];
$mtIcons   = ['💉', '🥄', '🕯️', '⚗️'];

// Craft recipes [mt0, mt1, mt2, mt3] required
$recipes = [
    0 => ['mt' => [1,1,0,2], 'name' => 'Methamphetamine'],
    1 => ['mt' => [2,2,3,2], 'name' => 'Amphetamine'],
    2 => ['mt' => [3,1,3,2], 'name' => 'Krakadzili'],
    3 => ['mt' => [4,3,4,2], 'name' => 'Heroine'],
];

$drugs    = [0,0,0,0];
$mt       = [0,0,0,0];
$addiction = 0.0;

if ($player !== '' && file_exists($vaultPath)) {
    $lines = file($vaultPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode("\t", $line);
        if (count($parts) < 3) continue;
        $key = trim($parts[1]);
        $val = trim($parts[2]);

        for ($i = 0; $i < 4; $i++) {
            if ($key === "{$player}_d{$i}") $drugs[$i] = intval($val);
            if ($key === "{$player}_m{$i}") $mt[$i]    = intval($val);
        }
        if ($key === "{$player}_addict") $addiction = intval($val) / 10.0;
    }
}

// Can craft?
$canCraft = [];
for ($i = 0; $i < 4; $i++) {
    $r = $recipes[$i]['mt'];
    $canCraft[$i] = ($mt[0]>=$r[0] && $mt[1]>=$r[1] && $mt[2]>=$r[2] && $mt[3]>=$r[3]);
}

$addPct = min(100, $addiction);
$addColor = $addPct >= 80 ? '#ff3b3b' : ($addPct >= 50 ? '#ff9500' : '#30d158');
?>
<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;600;800&display=swap');

  :root {
    --bg:        #0a0c10;
    --surface:   #111520;
    --card:      #161c2a;
    --border:    #1e2a40;
    --accent:    #00e5ff;
    --accent2:   #7b2fff;
    --gold:      #ffd60a;
    --red:       #ff3b3b;
    --green:     #30d158;
    --orange:    #ff9500;
    --text:      #c8d6f0;
    --muted:     #4a5568;
    --glow:      0 0 18px rgba(0,229,255,.35);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  html, body {
    width: 100%; height: 100%;
    background: var(--bg);
    font-family: 'Exo 2', sans-serif;
    color: var(--text);
    overflow-x: hidden;
  }

  /* Animated background grid */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background-image:
      linear-gradient(rgba(0,229,255,.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,229,255,.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }

  .wrap {
    position: relative; z-index: 1;
    max-width: 700px; margin: 0 auto;
    padding: 16px 12px 32px;
  }

  /* ── Header ── */
  .header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
  }
  .header-title {
    font-size: 22px; font-weight: 800; letter-spacing: 2px;
    text-transform: uppercase;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .header-sub {
    font-family: 'Share Tech Mono', monospace;
    font-size: 11px; color: var(--muted);
    letter-spacing: 1px; text-transform: uppercase;
    margin-top: 2px;
  }

  /* Addiction bar */
  .addiction-block {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
  }
  .addiction-label {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px;
    font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
    color: var(--muted);
  }
  .addiction-pct {
    font-family: 'Share Tech Mono', monospace;
    font-size: 14px;
    color: <?= $addColor ?>;
  }
  .bar-track {
    height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;
  }
  .bar-fill {
    height: 100%; border-radius: 4px;
    width: <?= $addPct ?>%;
    background: linear-gradient(90deg, var(--green), <?= $addColor ?>);
    transition: width .6s ease;
  }
  .addiction-warn {
    margin-top: 8px;
    font-size: 11px; color: var(--red);
    font-family: 'Share Tech Mono', monospace;
    display: <?= $addPct >= 80 ? 'block' : 'none' ?>;
  }

  /* ── Section title ── */
  .section-title {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 2px;
    color: var(--muted);
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 8px;
  }
  .section-title::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
  }

  /* ── Item grid ── */
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }

  .item-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px;
    display: flex; align-items: center; gap: 12px;
    position: relative;
    transition: border-color .2s, box-shadow .2s;
  }
  .item-card.has-items {
    border-color: rgba(0,229,255,.3);
  }
  .item-card.has-items:hover {
    border-color: var(--accent);
    box-shadow: var(--glow);
  }

  .item-icon {
    font-size: 26px; flex-shrink: 0;
    width: 42px; height: 42px;
    background: var(--surface);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--border);
  }
  .item-info { flex: 1; min-width: 0; }
  .item-name {
    font-size: 12px; font-weight: 600;
    color: var(--text); white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
  }
  .item-count {
    font-family: 'Share Tech Mono', monospace;
    font-size: 20px; font-weight: 700; line-height: 1.1;
    color: var(--accent);
  }
  .item-count.zero { color: var(--muted); }

  /* ── Craft section ── */
  .craft-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 10px;
    position: relative;
    overflow: hidden;
  }
  .craft-card.craftable {
    border-color: rgba(123,47,255,.5);
  }
  .craft-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: var(--accent2);
    opacity: 0;
    transition: opacity .2s;
  }
  .craft-card.craftable::before { opacity: 1; }

  .craft-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
  }
  .craft-name {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700;
  }
  .craft-owned {
    font-family: 'Share Tech Mono', monospace;
    font-size: 12px; color: var(--muted);
    background: var(--surface);
    padding: 2px 8px; border-radius: 20px;
    border: 1px solid var(--border);
  }

  .recipe-row {
    display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;
  }
  .recipe-item {
    display: flex; align-items: center; gap: 4px;
    font-size: 11px;
    padding: 3px 8px; border-radius: 20px;
    border: 1px solid var(--border);
    background: var(--surface);
  }
  .recipe-item.ok   { border-color: rgba(48,209,88,.4);  color: var(--green); }
  .recipe-item.bad  { border-color: rgba(255,59,59,.3);  color: var(--red);   }
  .recipe-icon { font-size: 13px; }

  .craft-btn {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: none; cursor: pointer;
    font-family: 'Exo 2', sans-serif;
    font-size: 13px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    transition: all .2s;
  }
  .craft-btn.active {
    background: linear-gradient(135deg, var(--accent2), #4a1fff);
    color: #fff;
    box-shadow: 0 0 18px rgba(123,47,255,.4);
  }
  .craft-btn.active:hover {
    transform: translateY(-1px);
    box-shadow: 0 0 26px rgba(123,47,255,.6);
  }
  .craft-btn.inactive {
    background: var(--surface);
    color: var(--muted);
    border: 1px solid var(--border);
    cursor: not-allowed;
  }

  /* ── Tip box ── */
  .tip-box {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 20px;
    font-size: 11px; color: var(--muted);
    font-family: 'Share Tech Mono', monospace;
    line-height: 1.8;
  }
  .tip-box span { color: var(--accent); }

  /* Craft notice */
  .craft-notice {
    background: rgba(123,47,255,.1);
    border: 1px solid rgba(123,47,255,.3);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    color: #b388ff;
    margin-bottom: 16px;
    font-family: 'Share Tech Mono', monospace;
    text-align: center;
  }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="header-title">☠ Drugs Inventory</div>
      <div class="header-sub">JailBreak Underground</div>
    </div>
    <div style="text-align:right">
      <div style="font-family:'Share Tech Mono',monospace;font-size:10px;color:var(--muted);">PLAYER</div>
      <div style="font-family:'Share Tech Mono',monospace;font-size:11px;color:var(--accent);word-break:break-all;max-width:160px"><?= htmlspecialchars($player) ?></div>
    </div>
  </div>

  <!-- Addiction bar -->
  <div class="addiction-block">
    <div class="addiction-label">
      <span>Narko Damokidebuleba</span>
      <span class="addiction-pct"><?= number_format($addPct, 1) ?>%</span>
    </div>
    <div class="bar-track"><div class="bar-fill"></div></div>
    <div class="addiction-warn">⚠ Damokidebuleba Dzaan Maglaa! Agar Shegidzlia Narko Gamoiyeno!</div>
  </div>

  <!-- Materials -->
  <div class="section-title">Samasalebo Masalebi</div>
  <div class="grid-2">
    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="item-card <?= $mt[$i] > 0 ? 'has-items' : '' ?>">
      <div class="item-icon"><?= $mtIcons[$i] ?></div>
      <div class="item-info">
        <div class="item-name"><?= $mtNames[$i] ?></div>
        <div class="item-count <?= $mt[$i] == 0 ? 'zero' : '' ?>"><?= $mt[$i] ?></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <!-- Drugs in inventory -->
  <div class="section-title">Narkotiki Inventori</div>
  <div class="grid-2" style="margin-bottom:24px">
    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="item-card <?= $drugs[$i] > 0 ? 'has-items' : '' ?>">
      <div class="item-icon"><?= $drugIcons[$i] ?></div>
      <div class="item-info">
        <div class="item-name"><?= $drugNames[$i] ?></div>
        <div class="item-count <?= $drugs[$i] == 0 ? 'zero' : '' ?>"><?= $drugs[$i] ?></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <!-- Crafting -->
  <div class="section-title">Damzadeba / Craft</div>
  <div class="craft-notice">
    ⚗️ Damzadeba xdeba Tavad Tamshelshi — /drugs &rarr; Damzadeba
  </div>

  <?php for ($i = 0; $i < 4; $i++):
    $r = $recipes[$i]['mt'];
    $can = $canCraft[$i];
  ?>
  <div class="craft-card <?= $can ? 'craftable' : '' ?>">
    <div class="craft-header">
      <div class="craft-name">
        <?= $drugIcons[$i] ?> <?= $drugNames[$i] ?>
      </div>
      <div class="craft-owned">Gaqvs: <?= $drugs[$i] ?></div>
    </div>

    <div class="recipe-row">
      <?php for ($j = 0; $j < 4; $j++): if ($r[$j] == 0) continue; ?>
      <div class="recipe-item <?= $mt[$j] >= $r[$j] ? 'ok' : 'bad' ?>">
        <span class="recipe-icon"><?= $mtIcons[$j] ?></span>
        <?= $mtNames[$j] ?>: <?= $mt[$j] ?>/<?= $r[$j] ?>
      </div>
      <?php endfor; ?>
    </div>

    <button class="craft-btn <?= $can ? 'active' : 'inactive' ?>"
      <?= !$can ? 'disabled' : '' ?>
      onclick="craftMsg(<?= $i ?>)">
      <?= $can ? '⚗ Damzade' : '🔒 Ar Gaqvs Masalebi' ?>
    </button>
  </div>
  <?php endfor; ?>

  <!-- Tip -->
  <div class="tip-box">
    <span>💡 Rogor Shevagrovo Masalebi?</span><br>
    Moklavi Patimari → Random masala Migiva<br>
    <span>Nemsi</span> · <span>Kovzi</span> · <span>Santebela</span> · <span>Qimiuri Narevi</span><br><br>
    <span>Craft</span> xdeba ingamshi: <span>/drugs</span> → Narkotikebis Damzadeba<br>
    Crafts asrulebs ingamshi, inventori mxolod aCvenebs!
  </div>

</div>

<script>
function craftMsg(idx) {
  var names = ['Methamphetamine','Amphetamine','Krakadzili','Heroine'];
  // Since crafting is nvault-based, we just tell the player to use in-game menu
  // A toast notification guides them
  showToast('⚗ Gamoyene /drugs → Damzadeba → ' + names[idx]);
}

function showToast(msg) {
  var t = document.createElement('div');
  t.style.cssText = [
    'position:fixed','bottom:20px','left:50%','transform:translateX(-50%)',
    'background:linear-gradient(135deg,#7b2fff,#4a1fff)',
    'color:#fff','padding:12px 20px','border-radius:10px',
    'font-family:Exo 2,sans-serif','font-size:13px','font-weight:600',
    'box-shadow:0 0 24px rgba(123,47,255,.6)',
    'z-index:9999','text-align:center','max-width:320px',
    'transition:opacity .4s','white-space:nowrap'
  ].join(';');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); }, 3500);
}
</script>
</body>
</html>
