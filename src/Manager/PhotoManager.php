<?php

namespace App\Manager;

use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Intervention\Image\ImageManagerStatic as Image;

class PhotoManager
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private Filesystem $fs;
    private string $uploadDir;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        string $projectDir
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->fs = new Filesystem();
        $this->uploadDir = $projectDir . '/public/uploads/photos';
    }

    public function create(UploadedFile $file, ?string $alt = null): Photo
    {
        $processedFile = $this->processImage($file);

        $photo = new Photo();
        $photo->setImageFile($processedFile);
        $photo->setAlt($alt);
        $photo->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($photo);
        $this->em->flush();

        $this->validate($photo);

        // 🔥 Générer la miniature après l'enregistrement
        $this->generateThumbnail($photo);

        return $photo;
    }

    private function validate(Photo $photo): void
    {
        $errors = $this->validator->validate($photo);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new BadRequestHttpException(json_encode($messages));
        }
    }

    public function delete(Photo $photo): void
    {
        $filePath = $this->uploadDir . '/' . $photo->getName();
        $thumbPath = $this->uploadDir . '/thumbs/' . $photo->getName();

        if ($this->fs->exists($filePath)) {
            try {
                $this->fs->remove($filePath);
            } catch (\Throwable $e) {
                // log ou rien
            }
        }

        if ($this->fs->exists($thumbPath)) {
            try {
                $this->fs->remove($thumbPath);
            } catch (\Throwable $e) {
                // log ou rien
            }
        }

        $this->em->remove($photo);
        $this->em->flush();
    }

    private function processImage(UploadedFile $file): UploadedFile
    {
        // 🔥 1. Vérifier la taille du fichier (max 5 Mo)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new BadRequestHttpException(json_encode([
                'file' => 'Le fichier est trop volumineux (5Mo maximum autorisé.'
            ]));
        }

        $image = Image::make($file->getPathname());

        // 🔥 2. Redimensionner si la largeur dépasse 1920px
        if ($image->width() > 1920) {
            $image->resize(1920, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // 🔥 3. Convertir en WebP avec une compression de qualité 75%
        $extension = 'webp';
        $mimeType = 'image/webp';
        $tempPath = sys_get_temp_dir() . '/' . uniqid('upload_', true) . '.' . $extension;

        $image->encode('webp', 75)->save($tempPath); // compression 75%

        // 🔥 4. Retourner le fichier prêt à être stocké par VichUploader
        return new UploadedFile(
            $tempPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp',
            $mimeType,
            null,
            true
        );
    }


    private function generateThumbnail(Photo $photo): void
    {
        $sourcePath = $this->uploadDir . '/' . $photo->getName();
        $thumbsDir = $this->uploadDir . '/thumbs';

        if (!$this->fs->exists($thumbsDir)) {
            $this->fs->mkdir($thumbsDir);
        }

        $thumbnailPath = $thumbsDir . '/' . $photo->getName();

        $image = Image::make($sourcePath);

        // Redimensionne pour que la miniature fasse 300px de large max
        $image->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->save($thumbnailPath, 75); // qualité 75%
    }
}
