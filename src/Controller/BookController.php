<?php

namespace App\Controller;

use App\Entity\Book;
use JMS\Serializer\Serializer;
use Doctrine\ORM\EntityManager;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;



class BookController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des livres.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des livres",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getBooks"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Books")
     *
     * @param BookRepository $bookRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'book', methods:['GET'])]
    public function getBookList(
        BookRepository $bookRepository,
        SerializerInterface $serializer, 
        Request $request,
        TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page',1);
        $limit = $request->get('limit',3);

        $idCache = "getAllBooks-".$page."-".$limit;

        $jsonBookList = $cachePool->get($idCache,function (ItemInterface $item) use ($bookRepository,$page,$limit,$serializer){
            $context = SerializationContext::create()->setGroups(['getBooks']);
            $item->tag("booksCache");
            $bookList = $bookRepository->findAllWithPagination($page,$limit);

            return $serializer->serialize($bookList,'json',$context);
        });
        

        return new JsonResponse($jsonBookList,Response::HTTP_OK,[],true);
    }

    #[Route('/api/book/{id}',name:"detailBook",methods:['GET'])]
    public function getDetailBook(Book $book,SerializerInterface $serializer):JsonResponse
    {

        $context = SerializationContext::create()->setGroups(['getBooks']);
        $context->setVersion("2.0");

        $jsonBook = $serializer->serialize($book,'json',$context);
        return new JsonResponse($jsonBook, Response::HTTP_OK,['accept'=>'json'],true);
    }

    #[Route('/api/book/{id}',name:'deleteBook',methods:['DELETE'])]
    public function deleteBook(
        Book $book, 
        EntityManagerInterface $manager,
        TagAwareCacheInterface $cachePool):JsonResponse{

        $cachePool->invalidateTags(["booksCache"]);
        $manager->remove($book);
        $manager->flush();

        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books',name:"createBook",methods:['POST'])]
    #[IsGranted('ROLE_ADMIN', message:"Vous n'avez pas les droits suffisants pour créer un livre")]
    public function createBook(
        SerializerInterface $serializer,
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGenerator, 
        AuthorRepository $authorRepository,
        ValidatorInterface $validator):JsonResponse{

        $book = $serializer->deserialize($request->getContent(),Book::class,'json');

        //On vérifie les erreurs
        $errors = $validator->validate($book);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST,[],true);
        }

        // Récupération de l'ensemble des données envoyées sousforme de tableau
        $content = $request->toArray();

        $idAuthor=$content['idAuthor']??-1;
        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.

        $book->setAuthor($authorRepository->find($idAuthor));

        $manager->persist($book);
        $manager->flush();

        $context =  SerializationContext::create()->setGroups(['getBooks']);

        $jsonBook = $serializer->serialize($book,'json',$context);

        $location = $urlGenerator->generate('detailBook',['id'=>$book->getId()],UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,["location"=>$location],true);
    }

    #[Route('/api/Books/{id}',name:'updateBook',methods:['PUT'])]
    public function updateBook(
        SerializerInterface $serializer,
        Request $request,
        Book $currentBook,
        ValidatorInterface $validator,
        AuthorRepository $authorRepository,
        EntityManagerInterface $manager,
        TagAwareCacheInterface $cache):JsonResponse{

       $newBook = $serializer->deserialize($request->getContent(), Book::class,'json');
       $currentBook->setTitle($newBook->getTitle());
       $currentBook->setCovertext($newBook->getcoverText());

       //on vérifie les erreurs
       $errors = $validator->validate($currentBook);
       if($errors->count()>0){
        return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST,[],true);
       }

       $content = $request->toArray();
       $idAuthor = $content['idAuthor']??-1;

       $currentBook->setAuthor($authorRepository->find($idAuthor));

       //envoie en bdd
       $manager->persist($currentBook);
       $manager->flush();

       //on vide le cache
       $cache->invalidateTags(['booksCache']);

       return new JsonResponse(null,JsonResponse::HTTP_NO_CONTENT);
    }
}
