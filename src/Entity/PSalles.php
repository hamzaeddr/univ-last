<?php

namespace App\Entity;

use App\Repository\PSallesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PSallesRepository::class)]
class PSalles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $code;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $designation;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $abreviation;

    #[ORM\ManyToOne(targetEntity: PEtages::class, inversedBy: 'salles')]
    private $etage;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $xIP;

    #[ORM\Column(type: 'float', nullable: true)]
    private $etatPC;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $attente;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $groupe;

    #[ORM\OneToMany(mappedBy: 'salle', targetEntity: PlEmptime::class)]
    private $emptimes;

    #[ORM\OneToMany(mappedBy: 'xsalle', targetEntity: PlEmptime::class)]
    private $plEmptimes;

    public function __construct()
    {
        $this->emptimes = new ArrayCollection();
        $this->plEmptimes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    public function getAbreviation(): ?string
    {
        return $this->abreviation;
    }

    public function setAbreviation(?string $abreviation): self
    {
        $this->abreviation = $abreviation;

        return $this;
    }

    public function getEtage(): ?PEtages
    {
        return $this->etage;
    }

    public function setEtage(?PEtages $etage): self
    {
        $this->etage = $etage;

        return $this;
    }

    public function getXIP(): ?string
    {
        return $this->xIP;
    }

    public function setXIP(?string $xIP): self
    {
        $this->xIP = $xIP;

        return $this;
    }

    public function getEtatPC(): ?float
    {
        return $this->etatPC;
    }

    public function setEtatPC(?float $etatPC): self
    {
        $this->etatPC = $etatPC;

        return $this;
    }

    public function getAttente(): ?int
    {
        return $this->attente;
    }

    public function setAttente(?int $attente): self
    {
        $this->attente = $attente;

        return $this;
    }

    public function getGroupe(): ?string
    {
        return $this->groupe;
    }

    public function setGroupe(?string $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * @return Collection|PlEmptime[]
     */
    public function getEmptimes(): Collection
    {
        return $this->emptimes;
    }

    public function addEmptime(PlEmptime $emptime): self
    {
        if (!$this->emptimes->contains($emptime)) {
            $this->emptimes[] = $emptime;
            $emptime->setSalle($this);
        }

        return $this;
    }

    public function removeEmptime(PlEmptime $emptime): self
    {
        if ($this->emptimes->removeElement($emptime)) {
            // set the owning side to null (unless already changed)
            if ($emptime->getSalle() === $this) {
                $emptime->setSalle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PlEmptime>
     */
    public function getPlEmptimes(): Collection
    {
        return $this->plEmptimes;
    }

    public function addPlEmptime(PlEmptime $plEmptime): self
    {
        if (!$this->plEmptimes->contains($plEmptime)) {
            $this->plEmptimes[] = $plEmptime;
            $plEmptime->setXsalle($this);
        }

        return $this;
    }

    public function removePlEmptime(PlEmptime $plEmptime): self
    {
        if ($this->plEmptimes->removeElement($plEmptime)) {
            // set the owning side to null (unless already changed)
            if ($plEmptime->getXsalle() === $this) {
                $plEmptime->setXsalle(null);
            }
        }

        return $this;
    }
}
