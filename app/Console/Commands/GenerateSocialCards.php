<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\PageSeo;
use Illuminate\Console\Command;

/**
 * Cuts one social card per page at the size every network expects.
 *
 * The pictures on the site are shaped for the page they sit on, which means
 * some are portrait and none are 1200x630. A scraper that gets the wrong shape
 * crops it blindly, so the card is cut here instead: centre of the picture,
 * the ratio the networks use, saved once and served as a static file.
 */
final class GenerateSocialCards extends Command
{
    protected $signature = 'seo:cards {--force : Cut every card again, even the ones that already exist}';

    protected $description = 'Generate the 1200x630 social card of every page from its picture';

    public function handle(): int
    {
        $directory = public_path(ltrim(PageSeo::CARD_DIRECTORY, '/'));

        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            $this->error("Could not create {$directory}");

            return self::FAILURE;
        }

        $made = 0;
        $kept = 0;
        $missing = [];

        foreach (PageSeo::pages() as $component => $entry) {
            $source = public_path(ltrim($entry['image'], '/'));
            $target = public_path(ltrim(PageSeo::cardPath($entry['group']), '/'));

            if (! is_file($source)) {
                $missing[] = "{$component}: {$entry['image']}";

                continue;
            }

            if (is_file($target) && ! $this->option('force') && filemtime($target) >= filemtime($source)) {
                $kept++;

                continue;
            }

            if (! $this->cut($source, $target)) {
                $missing[] = "{$component}: could not read {$entry['image']}";

                continue;
            }

            $made++;
        }

        $this->info("Cards written: {$made}, already current: {$kept}");

        foreach ($missing as $problem) {
            $this->warn("No card for {$problem}");
        }

        return $missing === [] ? self::SUCCESS : self::FAILURE;
    }

    /** Centre-crop one picture to the card size and save it as a JPEG. */
    private function cut(string $source, string $target): bool
    {
        $picture = @imagecreatefromjpeg($source);

        if ($picture === false) {
            return false;
        }

        $width = imagesx($picture);
        $height = imagesy($picture);
        $ratio = PageSeo::CARD_WIDTH / PageSeo::CARD_HEIGHT;

        $cropWidth = $width;
        $cropHeight = (int) round($width / $ratio);

        if ($cropHeight > $height) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $ratio);
        }

        $card = imagecreatetruecolor(PageSeo::CARD_WIDTH, PageSeo::CARD_HEIGHT);

        imagecopyresampled(
            $card,
            $picture,
            0,
            0,
            (int) round(($width - $cropWidth) / 2),
            // faces and skylines sit above the middle, so the crop is taken
            // from the upper third of a tall picture rather than its centre
            (int) round(($height - $cropHeight) / 3),
            PageSeo::CARD_WIDTH,
            PageSeo::CARD_HEIGHT,
            $cropWidth,
            $cropHeight,
        );

        $written = imagejpeg($card, $target, 76);

        imagedestroy($card);
        imagedestroy($picture);

        return $written;
    }
}
