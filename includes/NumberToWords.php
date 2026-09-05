<?php
/**
 * Converts a rupee amount into Indian-style words, e.g.
 * 51000 -> "Rupees Fifty One Thousand Only"
 * 4500000 -> "Rupees Forty Five Lakh Only"
 */
class NumberToWords
{
    private static array $ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private static array $tens = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function convertRupees(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = 'Rupees ' . self::convertNumber($rupees);
        if ($paise > 0) {
            $words .= ' and ' . self::convertNumber($paise) . ' Paise';
        }

        return trim($words) . ' Only';
    }

    private static function convertNumber(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $remainder = $number % 100;

        $parts = [];
        if ($crore > 0) {
            $parts[] = self::convertTwoDigit($crore) . ' Crore';
        }
        if ($lakh > 0) {
            $parts[] = self::convertTwoDigit($lakh) . ' Lakh';
        }
        if ($thousand > 0) {
            $parts[] = self::convertTwoDigit($thousand) . ' Thousand';
        }
        if ($hundred > 0) {
            $parts[] = self::$ones[$hundred] . ' Hundred';
        }
        if ($remainder > 0) {
            $parts[] = self::convertTwoDigit($remainder);
        }

        return implode(' ', $parts);
    }

    private static function convertTwoDigit(int $number): string
    {
        if ($number < 20) {
            return self::$ones[$number];
        }
        $tens = intdiv($number, 10);
        $ones = $number % 10;
        return trim(self::$tens[$tens] . ' ' . self::$ones[$ones]);
    }
}
