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


class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/pays/nouveau', name: 'country_new', methods: ['GET','POST'])]
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

        return $this->render('admin/country_new.html.twig');
    }

    #[Route('/pays', name: 'country_list')]
    public function countryList(CountryRepository $repo): Response
    {
        return $this->render('admin/country_list.html.twig', [
            'countries' => $repo->findAllOrdered(),
        ]);
    }

    #[Route('/zone/nouvelle', name: 'zone_new', methods: ['GET','POST'])]
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
                    $zone->setPopulation((int)$request->request->get('population', 0));
                    $zone->setSymptomatic((int)$request->request->get('symptomatic', 0));
                    $zone->setPositive((int)$request->request->get('positive', 0));
                    $zone->setStatus($request->request->get('status'));
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

    #[Route('/point/nouveau', name: 'point_new', methods: ['GET','POST'])]
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
        ]);
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
            'zones' => $repo->findBy(['status' => ['orange', 'rouge']]),
        ]);
    }

    #[Route('/telecharger/devoir', name: 'download_devoir')]
    public function downloadDevoir(): BinaryFileResponse
    {
        $path = $this->getParameter('kernel.project_dir').'/TP _ Devoir DITI4 30_01_2025.pdf';
        return $this->file($path, 'TP_Devoir_DITI4_30_01_2025.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
