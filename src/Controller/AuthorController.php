<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthorController extends AbstractController
{
    #[Route('/authors', name: 'app_author',methods:['GET'])]
    public function getAllAuthor(SerializerInterface $serializer,AuthorRepository $authorRepository): JsonResponse
    {
        $authorList = $authorRepository->findAll();
        $jsonAuthorList = $serializer->serialize($authorList,'json',['groups'=>'getAuthors']);
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK,['accept'=>'json'],true);
            
    }

     #[Route('/api/author/{id}',name:"detailAuthor",methods:['GET'])]
    public function getDetailBook(Author $author,SerializerInterface $serializer):JsonResponse{

        $jsonAuthor = $serializer->serialize($author,'json',['groups'=>'getAuthors']);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK,['accept'=>'json'],true);
    }
}
