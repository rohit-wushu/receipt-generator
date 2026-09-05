<?php
/**
 * Renders a receipt as a JPEG image using GD, mirroring the layout used
 * by templates/receipt_html.php (used for the PDF). Kept as a separate
 * simple renderer so JPG export never depends on Imagick/Ghostscript -
 * only the near-universal GD extension.
 */
class ReceiptImageRenderer
{
    private const WIDTH = 1000;
    private const HEIGHT = 1360;
    private const MARGIN = 60;

    private $im;
    private string $fontRegular;
    private string $fontBold;

    private int $white;
    private int $black;
    private int $grey;
    private int $lightGrey;
    private int $primary;
    private int $secondary;

    public function __construct(private array $project)
    {
        $this->im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $this->fontRegular = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
        $this->fontBold = __DIR__ . '/../assets/fonts/DejaVuSans-Bold.ttf';

        $this->white = imagecolorallocate($this->im, 255, 255, 255);
        $this->black = imagecolorallocate($this->im, 34, 34, 34);
        $this->grey = imagecolorallocate($this->im, 90, 90, 90);
        $this->lightGrey = imagecolorallocate($this->im, 238, 238, 238);
        $this->primary = $this->allocateHex($project['primary_color'] ?: '#163823');
        $this->secondary = $this->allocateHex($project['secondary_color'] ?: '#e07b28');

        imagefill($this->im, 0, 0, $this->white);
    }

    public function render(array $receipt): string
    {
        $w = self::WIDTH;
        $margin = self::MARGIN;

        // Top band
        $bandH = 150;
        imagefilledrectangle($this->im, 0, 0, $w, $bandH, $this->primary);
        $this->text($this->fontBold, 24, $margin, 55, $this->white, $this->project['company_name']);
        if (!empty($this->project['company_tagline'])) {
            $this->text($this->fontRegular, 12, $margin, 85, $this->secondary, strtoupper($this->project['company_tagline']));
        }
        if (!empty($this->project['logo_path'])) {
            $this->placeImage($this->project['logo_path'], $w - $margin, 30, 200, 90, true);
        }

        // Title
        $title = 'RECEIPT';
        $titleSize = 30;
        $box = imagettfbbox($titleSize, 0, $this->fontBold, $title);
        $titleWidth = $box[2] - $box[0];
        $titleX = (int) (($w - $titleWidth) / 2);
        $titleY = $bandH + 55;
        $this->text($this->fontBold, $titleSize, $titleX, $titleY, $this->black, $title);
        imagefilledrectangle($this->im, $titleX, $titleY + 12, $titleX + $titleWidth, $titleY + 15, $this->secondary);

        $y = $titleY + 60;

        // Date / Receipt No (left) + Seal (right)
        $this->text($this->fontRegular, 14, $margin, $y, $this->grey, 'Date : ' . format_date_display($receipt['receipt_date']));
        $y += 26;
        $this->text($this->fontBold, 14, $margin, $y, $this->black, 'Receipt No. : ' . $receipt['receipt_no']);

        if (!empty($this->project['seal_path'])) {
            $this->placeImage($this->project['seal_path'], $w - $margin, $y - 90, 160, 160, true);
        }

        $y += 40;
        $this->text($this->fontRegular, 13, $margin, $y, $this->black, 'Received with thanks from');
        $y += 26;
        $this->text($this->fontBold, 16, $margin, $y, $this->black, $receipt['customer_name']);
        $y += 24;

        $addrLines = $this->wrap($this->fontRegular, 13, (string) ($receipt['customer_address'] ?? ''), $w - 2 * $margin);
        foreach ($addrLines as $line) {
            $this->text($this->fontRegular, 13, $margin, $y, $this->black, $line);
            $y += 20;
        }

        $y += 10;
        $projectLine = sprintf(
            'towards booking / payment for unit in our project "%s"%s.',
            $this->project['project_name'],
            !empty($this->project['location']) ? ', ' . $this->project['location'] : ''
        );
        foreach ($this->wrap($this->fontRegular, 13, $projectLine, $w - 2 * $margin) as $line) {
            $this->text($this->fontRegular, 13, $margin, $y, $this->black, $line);
            $y += 20;
        }

        // Details table
        $y += 15;
        $rows = [
            ['Unit No.', $receipt['unit_no'] ?: '-'],
            ['Payment Mode', $receipt['payment_mode']],
            ['Reference / UTR No.', $receipt['reference_no'] ?: '-'],
        ];
        if (!empty($receipt['remarks'])) {
            $rows[] = ['Remarks', $receipt['remarks']];
        }
        $labelW = 260;
        $tableW = $w - 2 * $margin;
        foreach ($rows as [$label, $value]) {
            $rowH = 38;
            imagefilledrectangle($this->im, $margin, $y, $margin + $labelW, $y + $rowH, $this->lightGrey);
            imagerectangle($this->im, $margin, $y, $margin + $tableW, $y + $rowH, imagecolorallocate($this->im, 200, 200, 200));
            imageline($this->im, $margin + $labelW, $y, $margin + $labelW, $y + $rowH, imagecolorallocate($this->im, 200, 200, 200));
            $this->text($this->fontRegular, 13, $margin + 14, $y + 24, $this->grey, $label);
            $this->text($this->fontRegular, 13, $margin + $labelW + 14, $y + 24, $this->black, (string) $value);
            $y += $rowH;
        }

        // Amount box
        $y += 25;
        $boxH = 110;
        imagerectangle($this->im, $margin, $y, $margin + $tableW, $y + $boxH, $this->primary);
        imagerectangle($this->im, $margin + 1, $y + 1, $margin + $tableW - 1, $y + $boxH - 1, $this->primary);
        $this->text($this->fontRegular, 13, $margin + 16, $y + 28, $this->grey, 'Received Amount');
        $this->text($this->fontBold, 22, $margin + 16, $y + 62, $this->primary, format_money((float) $receipt['amount']));
        $this->text($this->fontRegular, 12, $margin + 16, $y + 92, $this->grey, 'Rupees (in words): ' . $receipt['amount_words']);
        $y += $boxH + 30;

        $note = 'The above amount has been received towards booking/payment for the said unit and shall be adjusted against the total sale consideration.';
        foreach ($this->wrap($this->fontRegular, 13, $note, $tableW) as $line) {
            $this->text($this->fontRegular, 13, $margin, $y, $this->black, $line);
            $y += 20;
        }

        // Signature block
        $y += 40;
        $this->text($this->fontRegular, 13, $margin, $y, $this->black, 'Thanking you,');
        $y += 20;
        $this->text($this->fontRegular, 13, $margin, $y, $this->black, 'With Best Wishes');
        $y += 20;
        $this->text($this->fontBold, 13, $margin, $y, $this->black, 'For ' . $this->project['company_name']);

        $sigY = $y - 40;
        if (!empty($this->project['signature_path'])) {
            $this->placeImage($this->project['signature_path'], $w - $margin, $sigY, 180, 60, true);
        }
        $lineY = $sigY + 70;
        imageline($this->im, $w - $margin - 200, $lineY, $w - $margin, $lineY, $this->grey);
        $this->text($this->fontRegular, 12, $w - $margin - 200, $lineY + 18, $this->grey, $this->project['signatory_name'] ?: 'Director');
        $this->text($this->fontRegular, 11, $w - $margin - 200, $lineY + 36, $this->grey, '(Authorized Signatory)');

        // Bottom band
        $footerH = 130;
        $footerY = self::HEIGHT - $footerH;
        imagefilledrectangle($this->im, 0, $footerY, $w, self::HEIGHT, $this->primary);
        $this->text($this->fontBold, 18, $margin, $footerY + 35, $this->white, $this->project['company_name']);
        if (!empty($this->project['company_tagline'])) {
            $this->text($this->fontRegular, 11, $margin, $footerY + 55, $this->secondary, strtoupper($this->project['company_tagline']));
        }

        $fy = $footerY + 30;
        $contactLines = array_filter([
            $this->project['office_address'] ?? '',
            !empty($this->project['email']) ? 'Email: ' . $this->project['email'] : '',
            !empty($this->project['phone']) ? 'Phone: ' . $this->project['phone'] : '',
            !empty($this->project['website']) ? 'Web: ' . $this->project['website'] : '',
        ]);
        foreach ($contactLines as $line) {
            $this->text($this->fontRegular, 11, (int) ($w / 2 + 20), $fy, $this->white, $line);
            $fy += 20;
        }

        return $this->save($receipt['receipt_no']);
    }

    private function save(string $receiptNo): string
    {
        $relDir = 'storage/jpg';
        $destDir = app_root() . '/' . $relDir;
        ensure_dir($destDir);
        $relPath = $relDir . '/' . slugify_receipt_no($receiptNo) . '.jpg';
        imagejpeg($this->im, app_root() . '/' . $relPath, 92);
        imagedestroy($this->im);
        return $relPath;
    }

    private function text(string $font, float $size, int $x, int $y, int $color, string $text): void
    {
        imagettftext($this->im, $size, 0, $x, $y, $color, $font, $text);
    }

    /** @return string[] */
    private function wrap(string $font, float $size, string $text, int $maxWidth): array
    {
        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph));
            $current = '';
            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                $box = imagettfbbox($size, 0, $font, $candidate);
                $width = $box[2] - $box[0];
                if ($width > $maxWidth && $current !== '') {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $candidate;
                }
            }
            if ($current !== '') {
                $lines[] = $current;
            }
        }
        return $lines ?: [''];
    }

    /**
     * Places an image (logo/seal/signature) onto the canvas, flattening
     * any PNG transparency onto white first, scaled to fit within
     * $maxW x $maxH while preserving aspect ratio.
     */
    private function placeImage(string $relPath, int $x, int $y, int $maxW, int $maxH, bool $alignRight = false): void
    {
        $path = app_root() . '/' . $relPath;
        if (!is_file($path)) {
            return;
        }
        $info = @getimagesize($path);
        if (!$info) {
            return;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            default => null,
        };
        if (!$src) {
            return;
        }

        [$srcW, $srcH] = [imagesx($src), imagesy($src)];
        $scale = min($maxW / $srcW, $maxH / $srcH, 1);
        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);

        $flattened = imagecreatetruecolor($dstW, $dstH);
        imagefill($flattened, 0, 0, $this->white);
        imagealphablending($flattened, true);
        imagecopyresampled($flattened, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $dstX = $alignRight ? $x - $dstW : $x;
        imagecopy($this->im, $flattened, $dstX, $y, 0, 0, $dstW, $dstH);

        imagedestroy($src);
        imagedestroy($flattened);
    }

    private function allocateHex(string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            $hex = '163823';
        }
        [$r, $g, $b] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
        return imagecolorallocate($this->im, $r, $g, $b);
    }
}

function generate_receipt_jpg(array $project, array $receipt): string
{
    $renderer = new ReceiptImageRenderer($project);
    return $renderer->render($receipt);
}
