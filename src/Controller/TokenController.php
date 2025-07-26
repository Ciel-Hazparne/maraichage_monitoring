<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TokenController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient) {}

    #[Route('/token', name: 'api_token', methods: ['POST'])]
    public function getToken(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $data = $request->request->all();
        $session = $request->getSession();

        try {
            $response = $this->httpClient->request('POST', 'http://localhost:8000/api/auth_token', [
                'json' => [
                    'email' => $data['email'] ?? '',
                    'password' => $data['password'] ?? '',
                ],
            ]);

            $json = $response->toArray();
            $token = $json['token'] ?? null;

            if ($token) {
                $session->set('api_token', $token);
                $this->addFlash('success', "Connexion réussie");

                return $this->redirectToRoute('libelle_mesures_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->addFlash('danger', "Token manquant");

            return $this->redirectToRoute('libelle_mesures_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable) {
            $this->addFlash('warning', "Erreur de connexion");
            return $this->redirectToRoute('libelle_mesures_index', [], Response::HTTP_SEE_OTHER);
        }
    }
}