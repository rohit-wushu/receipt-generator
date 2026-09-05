<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../templates/receipt_html.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders a receipt to a PDF file on disk and returns the path relative
 * to the application root (e.g. "storage/pdf/GB-RCT-26-27-046.pdf").
 */
function generate_receipt_pdf(array $project, array $receipt): string
{
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('chroot', app_root());

    $dompdf = new Dompdf($options);
    $html = render_receipt_html($project, $receipt, true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $relDir = 'storage/pdf';
    $destDir = app_root() . '/' . $relDir;
    ensure_dir($destDir);

    $relPath = $relDir . '/' . slugify_receipt_no($receipt['receipt_no']) . '.pdf';
    file_put_contents(app_root() . '/' . $relPath, $dompdf->output());

    return $relPath;
}
