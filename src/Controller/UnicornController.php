<?php

namespace App\Controller;

use App\Entity\Unicorn;
use App\Exceptions\CannotSellTwiceException;
use App\Exceptions\InvalidEmailException;
use App\Repository\UnicornRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\ConnectionException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * @Route("/api/unicorn", name="api_unicorn_")
 */
class UnicornController extends SerializerAwareAbstractController
{

    /**
     * @Route("", name="unicorn_all", methods={"GET"})
     */
    public function getAllUnicorns(Request $request, UnicornRepository $unicornRepository): JsonResponse
    {
        try
        {
            $unicorns = $unicornRepository->findAll();

            $serializer = $this->getSerializer();
            return JsonResponse::fromJsonString($serializer->serialize($unicorns, 'json', ["groups"=>["allUnicorns"]]) );
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
    }

    /**
     * @Route("/{id}", name="unicorn_single", methods={"GET"})
     */
    public function getSingleUnicorn(Request $request, UnicornRepository $unicornRepository): JsonResponse
    {
        try{
            $id         = $request->get('id');
            $unicorn    = $unicornRepository->findOneBy(['id' => $id]);

            $serializer = $this->getSerializer();
            return JsonResponse::fromJsonString( $serializer->serialize($unicorn, 'json', ['groups' => ['singleUnicorn']]) );
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
    }

    /**
     * @Route("", name="unicorn_new", methods={"POST"})
     */
    public function createUnicorn(Request $request): JsonResponse
    {
        try{
            $serializer    = $this->getSerializer();
            /** @var Unicorn $unicorn */
            $unicorn       = $serializer->deserialize($request->getContent(), Unicorn::class, 'json');

            //Prevent sold flag to be set manually
            $unicorn->setSold(false);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($unicorn);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($unicorn, 'json', ["groups"=>["singleUnicorn"]]));
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
     * @Route("/{id}", name="unicorn_update", methods={"PUT"})
     */
    public function updateUnicorn(Request $request, UnicornRepository $unicornRepository): JsonResponse
    {
        try{
            $id = $request->get('id');
            /** @var Unicorn $un */
            $un = $unicornRepository->findOneBy(["id"=>$id]);

            $serializer    = $this->getSerializer();
            $serializer->deserialize($request->getContent(), Unicorn::class, 'json', array("object_to_populate" => $un));
            $entityManager = $this->getDoctrine()->getManager();

            //Prevent sold flag to be set manually
            $un->setSold(false);

            $entityManager->persist($un);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($un, 'json', ["groups"=>["singleUnicorn"]]));
        }catch (NotNormalizableValueException $e)
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
     * @Route("/{id}", name="unicorn_remove", methods={"DELETE"})
     */
    public function removeUnicorn(Request $request, UnicornRepository $unicornRepository): JsonResponse
    {

        try{
            $id = $request->get('id');
            $un = $unicornRepository->findOneBy(['id'=> $id ]);

            $serializer    = $this->getSerializer();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($un);
            $entityManager->flush();

            return JsonResponse::fromJsonString($serializer->serialize($un, 'json', ["groups"=>["singleUnicorn"]]));
        }
        catch (ForeignKeyConstraintViolationException $e)
        {
            return JsonResponse::fromJsonString("Cannot remove a Unicorn which has Posts assigned to it", 400);
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }

    }

    /**
     * @Route("/{id}/sell", name="unicorn_sell", methods={"GET"})
     */
    public function sellUnicorn(Request $request, UnicornRepository $unicornRepository, MailerInterface $mailer)
    {
        try{
            $jsonDecoded = json_decode($request->getContent(), true);
            $email = $jsonDecoded['email'];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidEmailException();
            }

            $id = $request->get('id');
            $un = $unicornRepository->findOneBy(['id'=> $id ]);

            // cannot sell a unicorn if already sold :-(
            if($un->isSold()){
                throw new CannotSellTwiceException();
            }

            $posts         = $un->getPosts();
            $pRemoved      = 0;
            $entityManager = $this->getDoctrine()->getManager();

            foreach ($posts as $post)
            {
                $entityManager->remove($post);
                $pRemoved++;
            }

            //set sold flag
            $un->setSold(true);
            $entityManager->persist($un);
            $entityManager->flush();
            //send email
            $mailer->send($this->contructMail($email, $un->getName(), $pRemoved));

            $serializer    = $this->getSerializer();
            return JsonResponse::fromJsonString($serializer->serialize($un, 'json', ["groups"=>["singleUnicorn"]]));
        }
        catch(ConnectionException $e)
        {
            return JsonResponse::fromJsonString("Database Connection issue", 500);
        }
        catch(InvalidEmailException $e)
        {
            return JsonResponse::fromJsonString("Provided invalid email", 400);
        }
        catch(\ErrorException $e)
        {
            return JsonResponse::fromJsonString("Provided data could not be used to create a new Unicorn object", 400);
        }
        catch(CannotSellTwiceException $e) {
            return JsonResponse::fromJsonString("Provided data could not be used to create a new Unicorn object", 400);
        }
    }


    private function contructMail(string $to, string $name ,int $amount): Email
    {
        $email = (new Email())
            ->from("unicorns@biler.be") // parametrize from emailaddress to fetch from parameters instead of hard coding
            ->to($to)
            ->subject('Congratz with your Unicorn purchase')
            ->text('The unicorn '.$name.' had '.$amount. ' posts. ');

        return $email;
    }
}
