<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UnicornRepository;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Post;
use App\Exceptions\CannotPostAReviewForSoldUnicorn;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * @Route("/api/post", name="api_post_")
 */
class PostController extends SerializerAwareAbstractController
{
    /**
     * @Route("", name="post_all", methods={"GET"})
     */
    public function getAllPosts(Request $request, PostRepository $postRepository): JsonResponse
    {
        $posts = $postRepository->findAll();

        $serializer = $this->getSerializer();
        return JsonResponse::fromJsonString($serializer->serialize($posts, 'json', ["groups"=>["allPosts"]]) );
    }

    /**
     * @Route("/by/{name}", name="post_all_by", methods={"GET"})
     */
    public function getAllPostsBy(Request $request, PostRepository $postRepository): JsonResponse
    {
        $name  = $request->get('name');
        $posts = $postRepository->findBy(['creator' => $name]);

        $serializer = $this->getSerializer();
        return JsonResponse::fromJsonString($serializer->serialize($posts, 'json', ["groups"=>["allPosts"]]) );
    }

    /**
     * @Route("/{id}", name="post_single", methods={"GET"})
     */
    public function getSinglePost(Request $request, PostRepository $postRepository): JsonResponse
    {
        try{
            $id    = $request->get('id');
            $post  = $postRepository->findOneBy(['id' => $id]);

            if(is_null($post)){
                return JsonResponse::fromJsonString("Post with provided id not found", 404);
            }


            $serializer = $this->getSerializer();
            return JsonResponse::fromJsonString( $serializer->serialize($post, 'json', ['groups' => ['singlePost']]) );
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
    }

    /**
     * @Route("", name="post_new", methods={"POST"})
     */
    public function createPost(Request $request, UnicornRepository $unicornRepository): JsonResponse
    {
        try{
            $jsonDecoded = json_decode($request->getContent(), true);

            $serializer = $this->getSerializer();
            /** @var Post $post */
            $post       = $serializer->deserialize($request->getContent(), Post::class, 'json');
            $unicorn    = $unicornRepository->findOneBy(array("id"=> $jsonDecoded['unicorn_id']));

            if($unicorn->isSold()){
                throw new CannotPostAReviewForSoldUnicorn();
            }
            $post->setUnicorn($unicorn);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($post, 'json', ["groups"=>["singlePost"]]));
        }catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
        catch(CannotPostAReviewForSoldUnicorn $e)
        {
            return JsonResponse::fromJsonString("Cannot write posts to unicorns which are already sold", 403);
        }
        catch(\ErrorException $e)
        {
            return JsonResponse::fromJsonString("Provided data could not be used to create a new Unicorn object", 400);
        }

    }

    /**
     * @Route("/{id}", name="post_update", methods={"PUT"})
     */
    public function updatePost(Request $request,PostRepository $postRepository, UnicornRepository $unicornRepository): JsonResponse
    {
        try{
            $jsonDecoded = json_decode($request->getContent(), true);

            $id = $request->get('id');
            /** @var Post $un */
            $post = $postRepository->findOneBy(["id"=>$id]);

            $serializer    = $this->getSerializer();
            $serializer->deserialize($request->getContent(), Post::class, 'json', array("object_to_populate" => $post));
            $entityManager = $this->getDoctrine()->getManager();

            $un = $unicornRepository->findOneBy(["id"=>$jsonDecoded['unicorn_id']]);
            $post->setUnicorn($un);

            $entityManager->persist($post);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($post, 'json', ["groups"=>["singlePost"]]));
        }
        catch (NotNormalizableValueException $e)
        {
            return JsonResponse::fromJsonString("Provided data could not be used to create a new Unicorn object", 400);
        }
        catch (NotEncodableValueException $e)
        {
            return JsonResponse::fromJsonString("Provided data could not be used to create a new Unicorn object", 400);
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
    }

    /**
     * @Route("/{id}", name="post_remove_own", methods={"DELETE"})
     */
    public function removeOwnPost(Request $request, PostRepository $postRepository): JsonResponse
    {
        try{
            $jsonDecoded = json_decode($request->getContent(), true);
            $creator     = $jsonDecoded['creator'];

            $id   = $request->get('id');
            /** @var Post $post */
            $post = $postRepository->findOneBy(['id'=> $id ]);

            if(is_null($post)){
                return JsonResponse::fromJsonString("Post with provided id not found", 404);
            }

            if($creator !== $post->getCreator()){
                return JsonResponse::fromJsonString("Forbidden", 403);
            }

            $serializer    = $this->getSerializer();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($post, 'json', ["groups"=>["singlePost"]]));
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
    }
}
