<?php
namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $status = null;

    #[ORM\Column(type: 'integer')]
    private ?int $population = null;

    #[ORM\Column(type: 'integer')]
    private ?int $symptomatic = null;

    #[ORM\Column(type: 'integer')]
    private ?int $positive = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: SurveillancePoint::class, cascade: ['persist', 'remove'])]
    private Collection $points;

    public function __construct()
    {
        $this->points = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;
        return $this;
    }

    public function getSymptomatic(): ?int
    {
        return $this->symptomatic;
    }

    public function setSymptomatic(int $symptomatic): self
    {
        $this->symptomatic = $symptomatic;
        return $this;
    }

    public function getPositive(): ?int
    {
        return $this->positive;
    }

    public function setPositive(int $positive): self
    {
        $this->positive = $positive;
        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return Collection<int, SurveillancePoint>
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(SurveillancePoint $point): self
    {
        if (!$this->points->contains($point)) {
            $this->points[] = $point;
            $point->setZone($this);
        }
        return $this;
    }

    public function removePoint(SurveillancePoint $point): self
    {
        if ($this->points->removeElement($point)) {
            if ($point->getZone() === $this) {
                $point->setZone(null);
            }
        }
        return $this;
    }
}
