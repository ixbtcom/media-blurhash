<?php


namespace Lukaswhite\MediaBlurhash;


use Bepsvpt\Blurhash\Facades\BlurHash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Ixbtcom\Common\Models\Media;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Class BlurhashMedia
 * @package Lukaswhite\MediaBlurhash
 */
class BlurhashMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $media;


    public function __construct($media)
    {

        $this->media = $media;
    }

    public function handle(): void
    {
        // 1. Получаем путь, пригодный для BlurHash
        $path = $this->localPath($this->media);

        // 2. Получаем размеры (быстро, без Intervention)
        [$w, $h] = getimagesize($path);

        // 3. Сохраняем метаданные, если нужно
        $this->media->width    ??= $w;
        $this->media->height   ??= $h;
        $this->media->blurhash ??= BlurHash::encode($path);

        $this->media->save();

        // 4. Чистим tmp‑файл, если он был создан
        $this->cleanup($path);
    }

    /** Вернёт путь к локальному файлу (может быть tmp) */
    private function localPath($media): string
    {
        // Драйвер диска
        if ($media->getDiskDriverName() === 'local') {
            // Absolute path inside storage
            return Storage::disk($media->disk)
                ->path($media->getPathRelativeToRoot());
        }

        // --- удалённый диск (s3, ftp …) ---

        $tmp = tempnam(sys_get_temp_dir(), 'blur_');
        $stream = $media->stream();              // уже готовый readStream

        if (! is_resource($stream)) {
            throw new \RuntimeException("Unable to open stream for media {$media->id}");
        }

        // скопировать поток
        stream_copy_to_stream($stream, fopen($tmp, 'w+'));
        fclose($stream);

        return $tmp;
    }

    /** Удаляем временный файл, если он в системной tmp‑директории */
    private function cleanup(string $path): void
    {
        if (Str::startsWith($path, sys_get_temp_dir()) && file_exists($path)) {
            @unlink($path);
        }
    }
}
