<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Manager\PhotoManager;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException; // 🆕 Import pour Validation

#[Route('/api/photos')]
class PhotoController extends AbstractController
{
    #[Route('', name: 'admin_photos_list', methods: ['GET'])]
    public function list(PhotoRepository $photoRepository): JsonResponse
    {
        $photos = $photoRepository->findAll();

        return $this->json($photos, 200, [], ['groups' => 'photo:read']);
    }

    #[Route('/upload', name: 'admin_photos_upload', methods: ['POST'])]
    public function upload(Request $request, PhotoManager $photoManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        $file = $request->files->get('file');
        $alt = $request->request->get('alt');
    
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'Fichier invalide'], Response::HTTP_BAD_REQUEST);
        }
    
        try {
            $photo = $photoManager->create($file, $alt);
            return $this->json($photo, Response::HTTP_CREATED, [], ['groups' => 'photo:read']);
        } 
        catch (BadRequestHttpException $e) {
            return $this->json([
                'error' => json_decode($e->getMessage(), true)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        catch (\Throwable $e) {
            // ✅ Version propre : erreur générique
            return $this->json([
                'error' => 'Erreur interne du serveur.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    


    #[Route('/{id}', name: 'admin_photos_delete', methods: ['DELETE'])]
    public function delete(Photo $photo, PhotoManager $manager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $manager->delete($photo);
            return $this->json(['message' => 'Image supprimée avec succès'], 200);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur lors de la suppression'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
