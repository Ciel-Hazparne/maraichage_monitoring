<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MesureTabController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient) {}

    #[Route('/mesures', name: 'mesures_index', methods: ['GET'])]
    public function index(): Response
    {
        $mesures = [];
        $baseUrl = 'http://localhost:8000/api/mesures?';
        $libelles = [
            'humidite_champ',
            'humidite_serre',
            'temp_champ',
            'temp_serre',
        ];

        foreach ($libelles as $libelle) {
            $url = $baseUrl . http_build_query([
                    'libelleMesure.libelle' => $libelle,
                ]);

            try {
                $response = $this->httpClient->request('GET', $url);
                $data = $response->toArray()['member'] ?? [];
                $mesures = array_merge($mesures, $data);
            } catch (\Throwable $e) {
                $this->addFlash('error', "Erreur pour $libelle : " . $e->getMessage());
            }
        }

        return $this->render('mesure/tab.html.twig', [
            'current_menu' => 'mesures',
            'mesures' => $mesures,
            'tableConfigs' => [
                ['title' => 'Humidité Serre (%)', 'libelle' => 'humidite_serre'],
                ['title' => 'Température Serre (°C)', 'libelle' => 'temp_serre'],
                ['title' => 'Humidité Champ (%)', 'libelle' => 'humidite_champ'],
                ['title' => 'Température Champ (°C)', 'libelle' => 'temp_champ'],
            ],
        ]);
    }
}
