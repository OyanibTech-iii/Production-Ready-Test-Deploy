<?php

namespace App\Entity;

use App\Repository\ChatroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatroomRepository::class)]
class Chatroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $participants;

    #[ORM\OneToMany(mappedBy: 'chatroom', targetEntity: ChatMessage::class, cascade: ['remove'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getParticipants(): Collection { return $this->participants; }
    public function addParticipant(User $user): self { if (!$this->participants->contains($user)) $this->participants->add($user); return $this; }
    public function removeParticipant(User $user): self { $this->participants->removeElement($user); return $this; }
    public function getMessages(): Collection { return $this->messages; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
