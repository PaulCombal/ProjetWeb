<?php

namespace EX\GrumpyBundle\Controller;
use EX\GrumpyBundle\Entity\Groupe;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use EX\GrumpyBundle\Entity\Produit;
use EX\GrumpyBundle\Entity\Commande;
use EX\GrumpyBundle\Entity\Panier;
use EX\GrumpyBundle\Form\ProduitType;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\HttpFoundation\Response;


class ShopController extends Controller
{





	public function view_productsAction(Request $request, $format = "") {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$products = $this->getDoctrine()
			->getRepository(Produit::class)
			->findAll();

		$temp = [];
		foreach ($products as &$product) {
			$temp[] = 
			[
				"name" => $product->getNom(),
				"price" => $product->getPrix() . '€',
				"description" => $product->getDescription(),
				"category" => $product->getCategorie(),
				"chemin_image" => "http://via.placeholder.com/350x150",
				"id" => $product->getId()
			];
		}

		unset($products);

		if ($format === "json") {
			return new Response(json_encode($temp));
		}
		else {
			return $this->render('@EXGrumpy/Forum/view_products.html.twig', ['products' => $temp]);
		}
	}





	public function view_productAction($product_id) {
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$product = $this->getDoctrine()
			->getRepository(Produit::class)
			->find($product_id);

		$product = 
		[	
			"name" => $product->getNom(),
			"price" => $product->getPrix() . '€',
			"description" => $product->getDescription(),
			"category" => $product->getCategorie(),
			"chemin_image" => "http://via.placeholder.com/350x150",
			"iconv(in_charset, out_charset, str)d" => $product_id,
			"id" => $product_id
		];

		return $this->render('@EXGrumpy/Forum/view_product.html.twig', $product);
	}





	public function add_productAction(Request $request)
	{
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		if (!$user->hasGroup('Membre BDE')) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$produit = new Produit();
		$form = $this->createForm(ProduitType::class, $produit);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->persist($produit);
			$entityManager->flush();

			return $this->redirectToRoute('ex_grumpy_add_product');
		}

		return $this->render(
				'@EXGrumpy/Forum/add_product.html.twig',
				['form' => $form->createView()]
		);
	}





	public function add_to_panierAction(Request $request, $product_id)
	{
		$produit = new Produit();
		$panier = new Panier();
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		
		$produit->setId($product_id);

		
		$panier->setIdProduit($produit);
		$panier->setIdUtilisateur($user); 
		

		$entityManager = $this->getDoctrine()->getManager();
		$entityManager->merge($panier);
		$entityManager->flush();

		return $this->redirectToRoute('ex_grumpy_view_products');
	}




	public function view_panierAction(Request $request)
	{
		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$paniers = $this->getDoctrine()
			->getRepository(Panier::class)
			->findBy
			(
				[ 'idUtilisateur' => $user],
				null,
				50,
				0
			);

		$idProduits = [];
		foreach ($paniers as &$panier) {
			$idProduits[] = $panier->getIdProduit();
		}

		$produits = $this->getDoctrine()
			->getRepository(Produit::class)
			->findByid($idProduits);



		$temp = [];
		foreach ($produits as &$produit) {
			$temp[] = 
			[
				"nom_du_produit" => $produit->getNom(), 
				"prix" => $produit->getPrix(), 
				"description" => $produit->getDescription(),
				"quantite" => count($this->getDoctrine()
			->getRepository(Panier::class)
			->findBy
			(
				["idProduit" => $produit->getId()],
				null,
				50,
				0
			)),
				"categorie" => $produit->getCategorie(),
			];
		}

		return $this->render('@EXGrumpy/Forum/view_panier.html.twig', ['produits' => $temp]);

	}





	public function validate_commandeAction(Request $request)
	{

		$user = $this->getUser();
		if ($user == null) {
			return $this->redirectToRoute('fos_user_security_login');
		}

		$paniers = $this->getDoctrine()
			->getRepository(Panier::class)
			->findBy
			(
				[ 'idUtilisateur' => $user],
				null,
				50	,
				0
			);

		

		foreach ($paniers as $item) {
			$commande = new Commande();
			$commande->setStatutCommande("en cours");
			$commande->setIdUtilisateur($user);
			$commande->setIdProduit($item->getIdProduit());
			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->persist($commande);
			$entityManager->flush();
		}

			

		$entityManager = $this->getDoctrine()->getManager();
		
		foreach ($paniers as &$panier) {
			$entityManager->remove($panier);
		}
		$entityManager->flush();

		return $this->redirectToRoute('ex_grumpy_view_panier');
	
		
	}
}
