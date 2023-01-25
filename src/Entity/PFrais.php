<?php

namespace App\Entity;

use App\Repository\PFraisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PFraisRepository::class)]
class PFrais
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
    private $categorie;

    #[ORM\Column(type: 'float', nullable: true)]
    private $montant;

    #[ORM\Column(type: 'string', length: 255)]
    private $ice;

    #[ORM\Column(type: 'smallint')]
    private $active;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $rubrique;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $Nature;

    #[ORM\OneToMany(mappedBy: 'frais', targetEntity: TOperationdet::class)]
    private $operationdets;

    #[ORM\ManyToOne(targetEntity: AcFormation::class, inversedBy: 'frais')]
    private $formation;

   
    public function __construct()
    {
        $this->operationdets = new ArrayCollection();
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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(?int $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getIce(): ?string
    {
        return $this->ice;
    }

    public function setIce(string $ice): self
    {
        $this->ice = $ice;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getRubrique(): ?string
    {
        return $this->rubrique;
    }

    public function setRubrique(?string $rubrique): self
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    public function getNature(): ?string
    {
        return $this->Nature;
    }

    public function setNature(?string $Nature): self
    {
        $this->Nature = $Nature;

        return $this;
    }

    /**
     * @return Collection|TOperationdet[]
     */
    public function getOperationdets(): Collection
    {
        return $this->operationdets;
    }

    public function addOperationdet(TOperationdet $operationdet): self
    {
        if (!$this->operationdets->contains($operationdet)) {
            $this->operationdets[] = $operationdet;
            $operationdet->setFrais($this);
        }

        return $this;
    }

    public function removeOperationdet(TOperationdet $operationdet): self
    {
        if ($this->operationdets->removeElement($operationdet)) {
            // set the owning side to null (unless already changed)
            if ($operationdet->getFrais() === $this) {
                $operationdet->setFrais(null);
            }
        }

        return $this;
    }

    public function getFormation(): ?AcFormation
    {
        return $this->formation;
    }

    public function setFormation(?AcFormation $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    
}
