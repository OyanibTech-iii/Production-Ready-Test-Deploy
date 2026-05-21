<?php

namespace App\Entity;

use App\Repository\UserQrCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserQrCodeRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['qr:read']],
    denormalizationContext: ['groups' => ['qr:write']]
)]
class UserQrCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['qr:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'qrCode', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['qr:read', 'qr:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['qr:read', 'qr:write', 'user:read'])]
    private ?string $identifier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['qr:read', 'user:read'])]         
    private ?string $qrCodePath = null;

    #[ORM\Column]
    #[Groups(['qr:read', 'user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getQrCodePath(): ?string
    {
        return $this->qrCodePath;
    }

    public function setQrCodePath(?string $qrCodePath): static
    {
        $this->qrCodePath = $qrCodePath;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
