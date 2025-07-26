<?php

namespace App\Controller;

use App\Form\LibelleMesureType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

#[Route('/libelle_mesures')]
class LibelleMesureController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    #[Route('/', name: 'libelle_mesures_index', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:8000/api/libelle_mesures');
            $data = $response->toArray();
            $libelleMesures = $data['member'] ?? [];
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur de connexion à l’API : ' . $e->getMessage());
            $libelleMesures = [];
        }

        return $this->render('libelle_mesure/index.html.twig', [
            'current_menu' => 'libelle_mesures',
            'libelle_mesures' => $libelleMesures,
        ]);
    }

    #[Route('/new', name: 'libelle_mesures_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $token = $this->getApiToken($request);
        $flashes = $request->getSession()->getFlashBag();

        $form = $this->createForm(LibelleMesureType::class);
        $form->add('Enregistrer', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->httpClient->request('POST', 'http://localhost:8000/api/libelle_mesures', [
                    'headers' => [
                        'Authorization' => "Bearer $token",
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($form->getData())
                ]);
                $this->addFlash('success', 'Libellé créé avec succès.');
                return $this->redirectToRoute('libelle_mesures_index');
            } catch (\Throwable $e) {
                if ($redirect = $this->handle401($e, $request->getSession()->getFlashBag())) {
                    return $redirect;
                }
                $this->addFlash('error', 'Erreur à la création : ' . $e->getMessage());
            }
        }

        return $this->render('libelle_mesure/new.html.twig', [
            'current_menu' => 'libelle_mesures',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'libelle_mesures_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $token = $this->getApiToken($request);
        $flashes = $request->getSession()->getFlashBag();

        try {
            $response = $this->httpClient->request('GET', "http://localhost:8000/api/libelle_mesures/$id", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ]
            ]);
            $libelleMesure = $response->toArray();
        } catch (\Throwable $e) {
            if ($redirect = $this->handle401($e, $flashes)) {
                return $redirect;
            }

            throw $this->createNotFoundException("Libellé non trouvé ou accès refusé.");
        }

        $form = $this->createForm(LibelleMesureType::class, $libelleMesure);
        $form->add('Modifier', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->httpClient->request('PATCH', "http://localhost:8000/api/libelle_mesures/$id", [
                    'headers' => [
                        'Authorization' => "Bearer $token",
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($form->getData())
                ]);
                $this->addFlash('success', 'Libellé modifié.');
                return $this->redirectToRoute('libelle_mesures_index');
            } catch (\Throwable $e) {
                if ($redirect = $this->handle401($e, $flashes)) {
                    return $redirect;
                }

                $this->addFlash('error', 'Erreur à la modification : ' . $e->getMessage());
            }
        }

        return $this->render('libelle_mesure/edit.html.twig', [
            'current_menu' => 'libelle_mesures',
            'form' => $form->createView(),
            'libelle_mesure' => $libelleMesure,
        ]);
    }


    #[Route('/{id}', name: 'libelle_mesures_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): RedirectResponse
    {
        $csrfToken = $request->request->get('_token');
        $apiToken = $this->getApiToken($request);
        $flashes = $request->getSession()->getFlashBag();

        if ($this->isCsrfTokenValid("delete$id", $csrfToken)) {
            try {
                $this->httpClient->request('DELETE', "http://localhost:8000/api/libelle_mesures/$id", [
                    'headers' => [
                        'Authorization' => "Bearer $apiToken"
                    ]
                ]);
                $this->addFlash('success', 'Libellé supprimé.');
            } catch (\Throwable $e) {
                if ($redirect = $this->handle401($e, $flashes)) {
                    return $redirect;
                }
                $this->addFlash('error', 'Erreur à la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('libelle_mesures_index');
    }


    private function getApiToken(Request $request): string
    {
        $token = $request->getSession()->get('api_token');

        if (!$token) {
            $this->addFlash('error', 'Veuillez vous connecter à l’API.');
            return $this->redirectToRoute('libelle_mesures_index');
        }

        return $token;
    }

    private function handle401(\Throwable $e, FlashBagInterface $flashes): ?RedirectResponse
    {
        if ($e instanceof ClientExceptionInterface && method_exists($e, 'getResponse')) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 401) {
                $flashes->add('error', 'Votre session a expiré. Veuillez vous reconnecter.');
                return new RedirectResponse($this->generateUrl('libelle_mesures_index'));
            }
        }

        return null;
    }

}
