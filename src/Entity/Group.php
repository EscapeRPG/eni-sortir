<?php
namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'groupList')]
    #[ORM\JoinTable(name: 'group_user')]
    private Collection $userList;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Event::class, cascade: ['persist', 'remove'])]
    private Collection $events;

    public function __construct()
    {
        $this->userList = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUserList(): Collection
    {
        return $this->userList;
    }

    public function addUserList(User $userList): static
    {
        if (!$this->userList->contains($userList)) {
            $this->userList->add($userList);
        }

        return $this;
    }

    public function removeUserList(User $userList): static
    {
        $this->userList->removeElement($userList);

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setGroup($this); // Attention ici, on met à jour la relation inverse
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getGroup() === $this) {
                $event->setGroup(null);
            }
        }

        return $this;
    }
}
