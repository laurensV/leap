<?php

namespace Smirik\QuizBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Smirik\QuizBundle\Model\Quiz as ModelQuiz;
use Smirik\QuizBundle\Entity\Question as Question;

/**
 * Smirik\QuizBundle\Entity\Quiz
 *
 * @ORM\Table(name="smirik_quiz")
 * @ORM\Entity(repositoryClass="Smirik\QuizBundle\Entity\QuizRepository")
 */
class Quiz extends ModelQuiz
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=100)
     */
    private $title;

    /**
     * @var text $description
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var datetime $time
     *
     * @ORM\Column(name="time", type="integer")
     */
    private $time = 0;

    /**
     * @var integer $num_questions
     *
     * @ORM\Column(name="num_questions", type="integer")
     */
    private $num_questions;

    /**
     * @var boolean $is_active
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $is_active = true;

    /**
     * @var boolean $is_opened
     *
     * @ORM\Column(name="is_opened", type="boolean", nullable=true)
     */
    private $is_opened = true;

    /**
     * @var date $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=TRUE)
     */
    protected $created_at;

    /**
     * @var date $updated_at
     *
     * @ORM\Column(type="datetime", nullable=TRUE)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated_at;

    /**
     * @ORM\ManyToMany(targetEntity="Question", mappedBy="quizes", cascade={"all"})
     */
    private $questions;
    
    public function __construct()
    {
      $this->questions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set time
     *
     * @param datetime $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return datetime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set num_questions
     *
     * @param integer $numQuestions
     */
    public function setNumQuestions($numQuestions)
    {
        $this->num_questions = $numQuestions;
    }

    /**
     * Get num_questions
     *
     * @return integer 
     */
    public function getNumQuestions()
    {
        return $this->num_questions;
    }

    /**
     * Set is_active
     *
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
    }

    /**
     * Get is_active
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set created_at
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    }

    /**
     * Get created_at
     *
     * @return datetime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
    }

    /**
     * Get updated_at
     *
     * @return datetime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set is_opened
     *
     * @param boolean $isOpened
     */
    public function setIsOpened($isOpened)
    {
        $this->is_opened = $isOpened;
    }

    /**
     * Get is_opened
     *
     * @return boolean 
     */
    public function getIsOpened()
    {
        return $this->is_opened;
    }

    /**
     * Add questions
     *
     * @param Smirik\QuizBundle\Entity\Question $questions
     */
    public function addQuestion(\Smirik\QuizBundle\Entity\Question $questions)
    {
        $this->questions[] = $questions;
    }

    /**
     * Get questions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getQuestions()
    {
        return $this->questions;
    }
}