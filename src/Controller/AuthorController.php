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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    public function createAuthor(
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGenerator, 
        ValidatorInterface $validator):JsonResponse{

        //création variable author et on vient deserialiser le contenu de author
        $author = $serializer->deserialize($request->getContent(),Author::class,'json');       

        //On vérifie les erreurs
        $errors = $validator->validate($author);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST,[],true);
        }
        
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

    #[Route('/api/authors/{id}',name:"updateAuthor",methods:['PUT'])]
    public function updateAuthor(SerializerInterface $serializer,Request $request,Author $currentAuthor,EntityManagerInterface $manager):JsonResponse{

        $updateAuthor = $serializer->deserialize($request->getContent(),Author::class,'json',[AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

        $manager->persist($updateAuthor);
        $manager->flush();

       return new JsonResponse(null,JsonResponse::HTTP_NO_CONTENT);

    }
}
