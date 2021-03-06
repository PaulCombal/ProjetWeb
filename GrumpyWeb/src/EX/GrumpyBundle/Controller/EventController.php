<?php

namespace EX\GrumpyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use EX\GrumpyBundle\Entity\Evenement;
use EX\GrumpyBundle\Entity\Commentaire;
use EX\GrumpyBundle\Form\CommentaireType;
use EX\GrumpyBundle\Entity\Inscription;
use EX\GrumpyBundle\Form\EvenementType;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
	public function add_eventAction(Request $request)
	{
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$evenement = new Evenement();
		$evenement->setStatut("idée");
		$evenement->setIdUtilisateur($user);
		$form = $this->createForm(EvenementType::class, $evenement);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->persist($evenement);
			$entityManager->flush();

			// ... do any other work - like sending them an email, etc
			// maybe set a "flash" success message for the user

			return $this->redirectToRoute('ex_grumpy_add_event');
		}

		return $this->render('@EXGrumpy/Forum/add_event.html.twig', ['form' => $form->createView()]);
	}


	public function add_commentaireAction(Request $request, $cat_comment, $event_id)
	{
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$evenement = $this->getDoctrine()
			->getRepository(Evenement::class)
			->findBy
			(
				[ 'id' => $event_id ],
				null,
				1,
				0
			);

		$commentaire = new Commentaire();
		$commentaire->setIdEvenement(current($evenement));
		$commentaire->setIdUtilisateur($user);
		$commentaire->setTypeContenu($cat_comment);



		$form = $this->createForm(CommentaireType::class, $commentaire);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->persist($commentaire);
			$entityManager->flush();

			// ... do any other work - like sending them an email, etc
			// maybe set a "flash" success message for the user

			return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
		}

		switch ($cat_comment) {
			case 'like':
				if(isset($likeExist)){
					return new Response("Vous avez deja mis un like");
				}
				else{
					$entityManager = $this->getDoctrine()->getManager();
					$entityManager->persist($commentaire);
					$entityManager->flush();
					return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
				}
				break;
			case 'image':
				return $this->render(
				'@EXGrumpy/Forum/add_commentaire_'.$cat_comment.'.html.twig', ['form' => $form->createView()]);
				break;
			case 'commentaire':
				return $this->render(
				'@EXGrumpy/Forum/add_commentaire_'.$cat_comment.'.html.twig', ['form' => $form->createView()]);
				break;
			default:
				die("Bvn in the matrix");
				break;
		}
	}



	public function view_ideeAction(Request $request) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$events = $this->getDoctrine()
			->getRepository(Evenement::class)
			->findBy
			(
				[ 'statut' => 'idée' ],
				null,
				10,
				0
			);

		$temp = [];
		foreach ($events as &$event) {
			$temp[] = 
			[
				"title" => $event->getNom(), 
				"price" => $event->getPrix(), 
				"start_date" => $event->getDateDebut(), 
				"repetition" => "Tous les " . $event->getRepetition() . " jours",
				"description" => $event->getDescription(),
				"statut" => $event->getStatut(),
				"chemin_image" => $event->getCheminImage(),
				"id" => $event->getId()
			];
		}

		unset($events);

		return $this->render('@EXGrumpy/Forum/view_idee.html.twig', ['events' => $temp]);
	}



	public function view_eventsAction(Request $request) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}
		
		$events = $this->getDoctrine()
			->getRepository(Evenement::class)
			->findBy
			(
				[ 'statut' => 'officiel' ],
				null,
				50,
				0
			);

		$temp = [];
		foreach ($events as &$event) {
			$temp[] = 
			[
				"title" => $event->getNom(), 
				"price" => $event->getPrix(), 
				"start_date" => $event->getDateDebut(), 
				"repetition" => "Tous les " . $event->getRepetition() . " jours",
				"description" => $event->getDescription(),
				"statut" => $event->getStatut(),
				"chemin_image" => $event->getCheminImage(),
				"id" => $event->getId()
			];
		}

		unset($events);

		return $this->render('@EXGrumpy/Forum/view_events.html.twig', ['events' => $temp]);
	}


	public function viewAction($event_id) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$isSubscribedAlready = $this->getDoctrine()
			->getRepository(Inscription::class)
			->findBy
			(
				[ 'idEvenement' => $event_id, 'idUtilisateur' => $user->getId() ],
				null,
				1,
				0
			);

		$isLikedAlready = $this->getDoctrine()
			->getRepository(Commentaire::class)
			->findBy
			(
				[ 'idEvenement' => $event_id, 'idUtilisateur' => $user->getId(), 'typeContenu' => 'like' ],
				null,
				1,
				0
			);

		$numlikes = $this->getDoctrine()
			->getRepository(Commentaire::class)
			->findBy
			(
				[ 'idEvenement' => $event_id, 'typeContenu' => 'like' ],
				null,
				null,
				0
			);

		$event = $this->getDoctrine()
			->getRepository(Evenement::class)
			->find($event_id);

		$commentaires = $this->getDoctrine()
			->getRepository(Commentaire::class)
			->findBy
			(
				[ 'idEvenement' => $event_id],
				null,
				50,
				0
			);

			$temp = [];
			
		foreach ($commentaires as &$commentaire) {

			$temp[] = 
			[
				"contenu" => $commentaire->getContenu(),
				"poster_name" => $commentaire->getIdUtilisateur(),
				"type" => $commentaire->getTypeContenu()
			];
		}

		$event = 
		[
			"title" => $event->getNom(), 
			"price" => $event->getPrix(), 
			"start_date" => $event->getDateDebut(), 
			"repetition" => "Tous les " . $event->getRepetition() . " jours",
			"description" => $event->getDescription(),
			"statut" => $event->getStatut(),
			"chemin_image" => $event->getCheminImage(),
			"event_id" => $event_id,
			"is_subscribed" => sizeof($isSubscribedAlready) > 0,
			"is_liked" => sizeof($isLikedAlready) > 0,
			"like_count" => sizeof($numlikes),
			"commentaires" => $temp,
			"is_bde" => $user->hasGroup('Membre BDE'),
			"is_cesi" => $user->hasGroup('Membre CESI')
		];


		return $this->render('@EXGrumpy/Forum/view_event.html.twig', $event);

	}



	public function subscribe_eventAction($event_id) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$isSubscribedAlready = $this->getDoctrine()
			->getRepository(Inscription::class)
			->findBy
			(
				[ 'idEvenement' => $event_id, 'idUtilisateur' => $user->getId() ],
				null,
				1,
				0
			);

		if (sizeof($isSubscribedAlready) > 0) {
			return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
		}

		$event = $this->getDoctrine()
			->getRepository(Evenement::class)
			->find($event_id);

		$inscription = new Inscription();
		$inscription->setIdEvenement($event);
		$inscription->setIdUtilisateur($user);

		$entityManager = $this->getDoctrine()->getManager();
		$entityManager->persist($inscription);
		$entityManager->flush();


		return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
	}

	public function like_eventAction($event_id) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$isLikedAlready = $this->getDoctrine()
			->getRepository(Commentaire::class)
			->findBy
			(
				[ 'idEvenement' => $event_id, 'idUtilisateur' => $user->getId(), 'typeContenu' => 'like' ],
				null,
				1,
				0
			);

		if (sizeof($isLikedAlready) > 0) {
			return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
		}

		$event = $this->getDoctrine()
			->getRepository(Evenement::class)
			->find($event_id);

		$commentaire = new Commentaire();
		$commentaire->setIdEvenement($event);
		$commentaire->setIdUtilisateur($user);
		$commentaire->setTypeContenu('like');

		$entityManager = $this->getDoctrine()->getManager();
		$entityManager->persist($commentaire);
		$entityManager->flush();


		return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
	}

	public function validate_eventAction($action, $event_id) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		if (!$user->hasGroup('Membre BDE')) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$event = $this->getDoctrine()
			->getRepository(Evenement::class)
			->find($event_id);

		if ($action == 'validate') {
			$event->setStatut("officiel");
		}
		else {
			$event->setStatut("idée");
		}

		$entityManager = $this->getDoctrine()->getManager();
		$entityManager->persist($event);
		$entityManager->flush();


		return $this->redirectToRoute('ex_grumpy_view_event', ['event_id' => $event_id]);
	}

}