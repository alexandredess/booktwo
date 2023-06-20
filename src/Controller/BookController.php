<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public function createBook(SerializerInterface $serializer,Request $request,EntityManagerInterface $manager,UrlGeneratorInterface $urlGenerator):JsonResponse{

        $book = $serializer->deserialize($request->getContent(),Book::class,'json');
        $manager->persist($book);
        $manager->flush();

        $jsonBook = $serializer->serialize($book,'json',['groups'=>"getBooks"]);

        $location = $urlGenerator->generate('detailBook',['id'=>$book->getId()],UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,["location"=>$location],true);
    }
}
