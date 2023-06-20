<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'book', methods:['GET'])]
    public function getBookList(BookRepository $bookRepository,SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList,'json',['groups'=>'getBooks']);

        return new JsonResponse($jsonBookList,Response::HTTP_OK,[],true);
    }

    #[Route('/api/book/{id}',name:"detailBook",methods:['GET'])]
    public function getDetailBook(Book $book,SerializerInterface $serializer):JsonResponse{

        $jsonBook = $serializer->serialize($book,'json',['groups'=>'getBooks']);
        return new JsonResponse($jsonBook, Response::HTTP_OK,['accept'=>'json'],true);
    }

    #[Route('/api/book/{id}',name:'deleteBook',methods:['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $manager):JsonResponse{

        $manager->remove($book);
        $manager->flush();

        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books',name:"createBook",methods:['POST'])]
    public function createBook(SerializerInterface $serializer,Request $request,EntityManagerInterface $manager,UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository):JsonResponse{

        $book = $serializer->deserialize($request->getContent(),Book::class,'json');

        // Récupération de l'ensemble des données envoyées sousforme de tableau
        $content = $request->toArray();

        $idAuthor=$content['idAuthor']??-1;
        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.

        $book->setAuthor($authorRepository->find($idAuthor));

        $manager->persist($book);
        $manager->flush();

        $jsonBook = $serializer->serialize($book,'json',['groups'=>"getBooks"]);

        $location = $urlGenerator->generate('detailBook',['id'=>$book->getId()],UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,["location"=>$location],true);
    }

    #[Route('/api/Books/{id}',name:'updateBook',methods:['PUT'])]
    public function updateBook(SerializerInterface $serializer,Request $request,Book $currentBook,AuthorRepository $authorRepository,EntityManagerInterface $manager):JsonResponse{

        $updateBook = $serializer->deserialize($request->getContent(),Book::class,'json',[AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);
        $content = $request->toArray();
        $idAuthor = $content['idAuthor']??-1;
        $updateBook->setAuthor($authorRepository->find($idAuthor));

        $manager->persist($updateBook);
        $manager->flush();

       return new JsonResponse(null,JsonResponse::HTTP_NO_CONTENT);
    }
}
