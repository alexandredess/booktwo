<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function getDetailAuthor(Author $author,SerializerInterface $serializer):JsonResponse{

        $jsonAuthor = $serializer->serialize($author,'json',['groups'=>'getAuthors']);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK,['accept'=>'json'],true);
    }

    #[Route('/api/authors/{id}',name:'deleteAuthor',methods:['DELETE'])]
    public function deleteAuthor(Author $author, EntityManagerInterface $manager):JsonResponse{

        $manager->remove($author);
        $manager->flush();

        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors',name:"createAuthor",methods:['POST'])]
    public function createAuthor(SerializerInterface $serializer,Request $request,EntityManagerInterface $manager,UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository):JsonResponse{

        //création variable author et on vient deserialiser le contenu de author
        $author = $serializer->deserialize($request->getContent(),Author::class,'json');       

        //on garde les données dans notre seau
        $manager->persist($author);
        //on expédie
        $manager->flush();
        //on reprend les "groups" créés auparavant
        $jsonAuthor = $serializer->serialize($author,'json',['groups'=>"getAuthors"]);
        //on génère une URL avec l'id de l'auteur créé
        $location = $urlGenerator->generate('detailAuthor',['id'=>$author->getId()],UrlGeneratorInterface::ABSOLUTE_URL);
        //on retourne un JSON avec le status et l'url 
        return new JsonResponse($jsonAuthor,Response::HTTP_CREATED,["location"=>$location],true);
    }
}
