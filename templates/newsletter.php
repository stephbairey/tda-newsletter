<?php
/** Full newsletter document: both pages. Expects $issue, $settings, $issueId.
 *  Query flags: ?bw=1 grayscale preview. */
$grayscale = !empty($_GET['bw']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>TDA Currents — <?= e($issue['issue']['date_label'] ?? $issueId) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;0,900;1,500;1,600;1,700&family=Archivo:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/newsletter.css">
</head>
<body class="<?= $grayscale ? 'grayscale' : '' ?>">

<div class="toolbar">
  <span class="toolbar-title">TDA Currents · <?= e($issue['issue']['date_label'] ?? $issueId) ?></span>
  <a class="toolbar-btn" href="?issue=<?= e($issueId) ?><?= $grayscale ? '' : '&amp;bw=1' ?>"><?= $grayscale ? 'Color preview' : 'B&amp;W preview' ?></a>
  <button class="toolbar-btn" onclick="window.print()">Save as PDF</button>
  <span class="toolbar-hint">In the print dialog: Save as PDF, scale 100%, margins None, no headers/footers.</span>
</div>

<?php include __DIR__ . '/page1.php'; ?>
<?php include __DIR__ . '/page2.php'; ?>

<?php if (!empty($_GET['measure'])): ?>
<script>
function tdaMeasure() {
  var out = [];
  document.querySelectorAll('.sheet').forEach(function (s, i) {
    var kids = [];
    Array.prototype.forEach.call(s.children, function (c) {
      var r = c.getBoundingClientRect();
      var st = getComputedStyle(c);
      var mt = parseFloat(st.marginTop) || 0, mb = parseFloat(st.marginBottom) || 0;
      kids.push((c.getAttribute('class') || c.tagName).split(' ')[0] + ':' + Math.round(r.height + mt + mb));
    });
    var prev = s.style.height;
    s.style.height = 'auto';
    var natural = s.getBoundingClientRect().height;
    s.style.height = prev;
    out.push('sheet' + (i + 1) + ' natural=' + Math.round(natural) + '/1056 [' + kids.join(' ') + ']');
  });
  var el = document.getElementById('measure-out');
  if (!el) { el = document.createElement('div'); el.id = 'measure-out'; document.body.appendChild(el); }
  el.textContent = 'MEASURE: ' + out.join(' || ');
}
window.addEventListener('load', tdaMeasure);
setTimeout(tdaMeasure, 3000);
tdaMeasure();
</script>
<?php endif; ?>
</body>
</html>
