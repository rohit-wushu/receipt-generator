<?php
/**
 * Renders the receipt as a self-contained HTML string (inline CSS, no
 * external assets) so it can be shown in the browser and fed to Dompdf
 * unchanged.
 *
 * @param array $project       Row from `projects`.
 * @param array $receipt       Row from `receipts` (plus computed fields).
 * @param bool  $forFilesystem If true, image srcs are resolved to absolute
 *                             filesystem paths (needed by Dompdf). If false,
 *                             they are resolved to web URLs (for the browser).
 */
function render_receipt_html(array $project, array $receipt, bool $forFilesystem = false): string
{
    $resolveImage = static function (?string $relPath) use ($forFilesystem): ?string {
        if (!$relPath) {
            return null;
        }
        if ($forFilesystem) {
            $abs = app_root() . '/' . $relPath;
            return is_file($abs) ? $abs : null;
        }
        return app_base_url() . '/' . $relPath;
    };

    $logo = $resolveImage($project['logo_path'] ?? null);
    $seal = $resolveImage($project['seal_path'] ?? null);
    $signature = $resolveImage($project['signature_path'] ?? null);

    $primary = $project['primary_color'] ?: '#163823';
    $secondary = $project['secondary_color'] ?: '#e07b28';

    $companyName = e($project['company_name']);
    $tagline = e($project['company_tagline'] ?? '');
    $projectName = e($project['project_name']);
    $location = e($project['location'] ?? '');
    $officeAddress = e($project['office_address'] ?? '');
    $email = e($project['email'] ?? '');
    $phone = e($project['phone'] ?? '');
    $website = e($project['website'] ?? '');
    $signatoryName = e($project['signatory_name'] ?? 'Director');

    $receiptNo = e($receipt['receipt_no']);
    $receiptDate = e(format_date_display($receipt['receipt_date']));
    $customerName = e($receipt['customer_name']);
    $customerAddress = nl2br(e($receipt['customer_address'] ?? ''));
    $unitNo = e($receipt['unit_no'] ?: '—');
    $amount = e(format_money((float) $receipt['amount']));
    $amountWords = e($receipt['amount_words']);
    $paymentMode = e($receipt['payment_mode']);
    $referenceNo = e($receipt['reference_no'] ?: '—');
    $remarks = e($receipt['remarks'] ?? '');

    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: #222;
        font-size: 13px;
    }
    .sheet {
        width: 780px;
        margin: 0 auto;
        background: #fff;
    }
    .band-top {
        background: <?= $primary ?>;
        color: #fff;
        padding: 18px 28px;
    }
    .band-top table { width: 100%; border-collapse: collapse; }
    .brand-name {
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 0;
    }
    .brand-tagline {
        font-size: 11px;
        color: <?= $secondary ?>;
        letter-spacing: 1px;
        margin: 2px 0 0;
    }
    .logo-cell img { height: 50px; }
    .title-row {
        text-align: center;
        padding: 18px 0 6px;
    }
    .title-row h1 {
        font-size: 26px;
        letter-spacing: 2px;
        margin: 0;
        border-bottom: 3px solid <?= $secondary ?>;
        display: inline-block;
        padding-bottom: 6px;
    }
    .content {
        padding: 10px 34px 20px;
    }
    .meta-row {
        width: 100%;
        margin-top: 6px;
    }
    .meta-row td { vertical-align: top; padding: 3px 0; }
    .label { color: #666; width: 160px; display: inline-block; }
    .seal-wrap { text-align: center; padding: 6px 0; }
    .seal-wrap img { height: 100px; }
    table.details {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }
    table.details th, table.details td {
        border: 1px solid #ccc;
        padding: 8px 10px;
        text-align: left;
        font-size: 12px;
    }
    table.details th {
        background: #f2f2f2;
        width: 220px;
        color: #444;
    }
    .amount-box {
        margin-top: 16px;
        border: 2px solid <?= $primary ?>;
        padding: 12px 16px;
    }
    .amount-box .amt {
        font-size: 20px;
        font-weight: bold;
        color: <?= $primary ?>;
    }
    .words {
        margin-top: 6px;
        font-style: italic;
        color: #444;
    }
    .sign-row {
        margin-top: 36px;
        width: 100%;
    }
    .sign-row img { height: 55px; }
    .sign-line {
        border-top: 1px solid #444;
        width: 200px;
        margin-top: 4px;
        padding-top: 4px;
        font-size: 12px;
        color: #444;
    }
    .band-bottom {
        background: <?= $primary ?>;
        color: #fff;
        padding: 14px 28px;
        font-size: 11px;
        margin-top: 30px;
    }
    .band-bottom td { padding: 2px 10px; }
    .footer-brand {
        font-weight: bold;
        letter-spacing: 1px;
        font-size: 15px;
    }
    .footer-tagline {
        color: <?= $secondary ?>;
        font-size: 10px;
        letter-spacing: 1px;
    }
</style>
</head>
<body>
<div class="sheet">

  <div class="band-top">
    <table>
      <tr>
        <td>
          <div class="brand-name"><?= $companyName ?></div>
          <?php if ($tagline): ?><div class="brand-tagline"><?= $tagline ?></div><?php endif; ?>
        </td>
        <td class="logo-cell" style="text-align:right">
          <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="logo"><?php endif; ?>
        </td>
      </tr>
    </table>
  </div>

  <div class="title-row">
    <h1>RECEIPT</h1>
  </div>

  <div class="content">
    <table class="meta-row">
      <tr>
        <td style="width:60%">
          <div><span class="label">Date</span> : <?= $receiptDate ?></div>
          <div><span class="label">Receipt No.</span> : <strong><?= $receiptNo ?></strong></div>
        </td>
        <td style="width:40%; text-align:right">
          <?php if ($seal): ?><img src="<?= e($seal) ?>" alt="seal" style="height:90px"><?php endif; ?>
        </td>
      </tr>
    </table>

    <p style="margin-top:14px">
      Received with thanks from<br>
      <strong style="font-size:15px"><?= $customerName ?></strong><br>
      <?= $customerAddress ?>
    </p>

    <p>
      towards booking / payment for unit in our project
      <strong>&ldquo;<?= $projectName ?>&rdquo;</strong><?php if ($location): ?>, <?= $location ?><?php endif; ?>.
    </p>

    <table class="details">
      <tr><th>Unit No.</th><td><?= $unitNo ?></td></tr>
      <tr><th>Payment Mode</th><td><?= $paymentMode ?></td></tr>
      <tr><th>Reference / UTR No.</th><td><?= $referenceNo ?></td></tr>
      <?php if ($remarks !== ''): ?>
      <tr><th>Remarks</th><td><?= nl2br($remarks) ?></td></tr>
      <?php endif; ?>
    </table>

    <div class="amount-box">
      <div>Received Amount</div>
      <div class="amt"><?= $amount ?></div>
      <div class="words">Rupees (in words): <?= $amountWords ?></div>
    </div>

    <p style="margin-top:18px">
      The above amount has been received towards booking/payment for the said unit and shall
      be adjusted against the total sale consideration.
    </p>

    <table class="sign-row">
      <tr>
        <td>Thanking you,<br>With Best Wishes<br><strong>For <?= $companyName ?></strong></td>
        <td style="text-align:right">
          <?php if ($signature): ?><img src="<?= e($signature) ?>" alt="signature"><?php endif; ?>
          <div class="sign-line" style="margin-left:auto"><?= $signatoryName ?><br>(Authorized Signatory)</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="band-bottom">
    <table style="width:100%">
      <tr>
        <td style="width:55%">
          <div class="footer-brand"><?= $companyName ?></div>
          <?php if ($tagline): ?><div class="footer-tagline"><?= $tagline ?></div><?php endif; ?>
        </td>
        <td style="width:45%">
          <?php if ($officeAddress): ?><div><?= $officeAddress ?></div><?php endif; ?>
          <?php if ($email): ?><div>Email: <?= $email ?></div><?php endif; ?>
          <?php if ($phone): ?><div>Phone: <?= $phone ?></div><?php endif; ?>
          <?php if ($website): ?><div>Web: <?= $website ?></div><?php endif; ?>
        </td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>
    <?php
    return ob_get_clean();
}
