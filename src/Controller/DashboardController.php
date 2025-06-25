<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Country;
use App\Entity\Zone;
use App\Entity\SurveillancePoint;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CountryRepository;
use App\Repository\ZoneRepository;
use App\Repository\SurveillancePointRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;

/**
 * Dashboard and CRUD controller.
 */
class DashboardController extends AbstractController
{
    /** Suggested country names for the autocomplete list. */
    private const COUNTRY_NAMES = [
        'Sénégal', 'Mali', 'Mauritanie', 'Gambie', 'Guinée',
    ];

    /** Department names used for point suggestions. */
    private const DEPARTMENT_NAMES = [
        'Dakar', 'Pikine', 'Guédiawaye', 'Rufisque',
        'Thiès', 'Mbour', 'Tivaouane',
        'Diourbel', 'Bambey', 'Mbacké',
        'Fatick', 'Foundiougne', 'Gossas',
        'Kaolack', 'Guinguinéo', 'Nioro du Rip',
        'Kaffrine', 'Birkilane', 'Koungheul', 'Malem Hodar',
        'Louga', 'Kébémer', 'Linguère',
        'Saint-Louis', 'Dagana', 'Podor',
        'Matam', 'Kanel', 'Ranéro Ferlo',
        'Tambacounda', 'Bakel', 'Goudiry', 'Koumpentoum',
        'Kédougou', 'Salémata', 'Saraya',
        'Kolda', 'Velingara', 'Médina Yoro Foulah',
        'Sédhiou', 'Bounkiling', 'Goudomp',
        'Ziguinchor', 'Bignona', 'Oussouye'
    ];
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/pays/nouveau', name: 'country_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newCountry(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            if ($name !== '') {
                $country = new Country();
                $country->setName($name);
                $em->persist($country);
                $em->flush();
                return $this->redirectToRoute('country_list');
            }
        }

        return $this->render('admin/country_new.html.twig', [
            'suggestions' => self::COUNTRY_NAMES,
        ]);
    }

    #[Route('/pays', name: 'country_list')]
    public function countryList(CountryRepository $repo): Response
    {
        return $this->render('admin/country_list.html.twig', [
            'countries' => $repo->findAllOrdered(),
        ]);
    }

    #[Route('/pays/{id}/modifier', name: 'country_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editCountry(Country $country, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            if ($name !== '') {
                $country->setName($name);
                $em->flush();
                return $this->redirectToRoute('country_list');
            }
        }

        return $this->render('admin/country_edit.html.twig', [
            'country' => $country,
            'suggestions' => self::COUNTRY_NAMES,
        ]);
    }

    #[Route('/pays/{id}/supprimer', name: 'country_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deleteCountry(Country $country, EntityManagerInterface $em): Response
    {
        $em->remove($country);
        $em->flush();
        return $this->redirectToRoute('country_list');
    }

    #[Route('/zone/nouvelle', name: 'zone_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newZone(Request $request, EntityManagerInterface $em, CountryRepository $countries): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $countryId = $request->request->get('country');
            if ($name !== '' && $countryId) {
                $country = $countries->find($countryId);
                if ($country) {
                    $zone = new Zone();
                    $zone->setName($name);
                    $zone->setCountry($country);
                    $population = (int)$request->request->get('population', 0);
                    $symptomatic = (int)$request->request->get('symptomatic', 0);
                    $positive = (int)$request->request->get('positive', 0);
                    $zone->setPopulation($population);
                    $zone->setSymptomatic($symptomatic);
                    $zone->setPositive($positive);
                    $zone->setStatus($this->calculateStatus($population, $symptomatic, $positive));
                    $em->persist($zone);
                    $em->flush();
                    return $this->redirectToRoute('zone_list');
                }
            }
        }

        return $this->render('admin/zone_new.html.twig', [
            'countries' => $countries->findAllOrdered(),
        ]);
    }

    #[Route('/zone/{id}/modifier', name: 'zone_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editZone(Zone $zone, Request $request, EntityManagerInterface $em, CountryRepository $countries): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $countryId = $request->request->get('country');
            if ($name !== '' && $countryId) {
                $country = $countries->find($countryId);
                if ($country) {
                    $population = (int)$request->request->get('population', 0);
                    $symptomatic = (int)$request->request->get('symptomatic', 0);
                    $positive = (int)$request->request->get('positive', 0);
                    $zone->setName($name);
                    $zone->setCountry($country);
                    $zone->setPopulation($population);
                    $zone->setSymptomatic($symptomatic);
                    $zone->setPositive($positive);
                    $zone->setStatus($this->calculateStatus($population, $symptomatic, $positive));
                    $em->flush();
                    return $this->redirectToRoute('zone_list');
                }
            }
        }

        return $this->render('admin/zone_edit.html.twig', [
            'zone' => $zone,
            'countries' => $countries->findAllOrdered(),
        ]);
    }

    #[Route('/zone/{id}/supprimer', name: 'zone_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deleteZone(Zone $zone, EntityManagerInterface $em): Response
    {
        $em->remove($zone);
        $em->flush();
        return $this->redirectToRoute('zone_list');
    }

    #[Route('/point/nouveau', name: 'point_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newPoint(Request $request, EntityManagerInterface $em, ZoneRepository $zones): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $zoneId = $request->request->get('zone');
            if ($name !== '' && $zoneId) {
                $zone = $zones->find($zoneId);
                if ($zone) {
                    $point = new SurveillancePoint();
                    $point->setName($name);
                    $point->setZone($zone);
                    $em->persist($point);
                    $em->flush();
                    return $this->redirectToRoute('point_list');
                }
            }
        }

        return $this->render('admin/point_new.html.twig', [
            'zones' => $zones->findAll(),
            'suggestions' => self::DEPARTMENT_NAMES,
        ]);
    }

    #[Route('/point/{id}/modifier', name: 'point_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editPoint(SurveillancePoint $point, Request $request, EntityManagerInterface $em, ZoneRepository $zones): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $zoneId = $request->request->get('zone');
            if ($name !== '' && $zoneId) {
                $zone = $zones->find($zoneId);
                if ($zone) {
                    $point->setName($name);
                    $point->setZone($zone);
                    $em->flush();
                    return $this->redirectToRoute('point_list');
                }
            }
        }

        return $this->render('admin/point_edit.html.twig', [
            'point' => $point,
            'zones' => $zones->findAll(),
            'suggestions' => self::DEPARTMENT_NAMES,
        ]);
    }

    #[Route('/point/{id}/supprimer', name: 'point_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deletePoint(SurveillancePoint $point, EntityManagerInterface $em): Response
    {
        $em->remove($point);
        $em->flush();
        return $this->redirectToRoute('point_list');
    }

    #[Route('/points', name: 'point_list')]
    public function pointList(SurveillancePointRepository $repo): Response
    {
        return $this->render('admin/point_list.html.twig', [
            'points' => $repo->findAll(),
        ]);
    }

    #[Route('/zones', name: 'zone_list')]
    public function zoneList(ZoneRepository $repo): Response
    {
        return $this->render('admin/zone_list.html.twig', [
            'zones' => $repo->findAll(),
        ]);
    }

    #[Route('/zones/critiques', name: 'critical_zones')]
    public function criticalZones(ZoneRepository $repo): Response
    {
        return $this->render('admin/critical_zones.html.twig', [
            // only zones in red status are considered critical
            'zones' => $repo->findBy(['status' => 'rouge']),
        ]);
    }

    #[Route('/carte', name: 'view_map')]
    public function viewMap(ZoneRepository $repo, SurveillancePointRepository $points): Response
    {
        $apiKey = $this->getParameter('google_maps_api_key');

        $zones = [];
        foreach ($repo->findAll() as $zone) {
            $zones[] = [
                'name' => $zone->getName(),
                'status' => $zone->getStatus(),
                'population' => $zone->getPopulation(),
                'symptomatic' => $zone->getSymptomatic(),
                'positive' => $zone->getPositive(),
            ];
        }

        $pts = [];
        foreach ($points->findAll() as $point) {
            $pts[] = [
                'name' => $point->getName(),
                'zone' => $point->getZone()->getName(),
            ];
        }

        return $this->render('admin/view_map.html.twig', [
            'apiKey' => $apiKey,
            'zones' => $zones,
            'points' => $pts,
        ]);
    }

    private function calculateStatus(int $population, int $symptomatic, int $positive): string
    {
        if ($population <= 0) {
            return 'verte';
        }

        $rate = $positive / $population * 100;

        if ($rate >= 15) {
            return 'rouge';
        }

        if ($rate >= 5) {
            return 'orange';
        }

        return 'verte';
    }
}
