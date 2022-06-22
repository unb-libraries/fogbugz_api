<?php

namespace Drupal\fogbugz_api\FogBugz;

/**
 * FogBugz Case Information.
 * @link https://support.fogbugz.com/hc/en-us/articles/360011330713-FogBugz-XML-API-Editing-Cases-
 *
 * @package Drupal\fogbugz_api\FogBugz
 */
class FogBugzCase {

  /**
   * The case assignment id (number).
   *
   * @var int
   */
  protected $id;

  /**
   * The case title field.
   *
   * @var string
   */
  protected $title;

  /**
   * Boolean "open" value.
   *
   * @var bool
   */
  protected $isOpen;

  /**
   * The Connected Mailbox id.
   *
   * @var int
   */
  protected $mailboxId;

  /**
   * The case priority id.
   *
   * @var int
   */
  protected $priorityId;

  /**
   * The case priority name.
   *
   * @var string
   */
  protected $priority;

  /**
   * The case category id.
   *
   * @var int
   */
  protected $categoryId;

  /**
   * The case category name.
   *
   * @var string
   */
  protected $category;

  /**
   * The case requester name/email, also used for forward operation sTo.
   *
   * @var string
   */
  protected $customerEmail;

  /**
   * Array of case events.
   *
   * @var array
   */
  protected $events;

  /**
   * The Case project id.
   *
   * @var int
   */
  protected $projectId;

  /**
   * The Case project name.
   *
   * @var string
   */
  protected $project;

  /**
   * Array of the tags associated with a case.
   *
   * @var array
   */
  protected $tags;

  /**
   * Date the case was opened.
   *
   * @var \DateTime
   */
  protected $dateOpened;

  /**
   * Date the case was closed-.
   *
   * @var \DateTime
   */
  protected $dateClosed;

  /**
   * Current case status.
   *
   * @var string
   */
  protected $status;

  /**
   * Array of custom field data.
   *
   * @var array
   */
  protected $customFields;

  /**
   * Create a case from xml data.
   *
   * @param \SimpleXMLElement $xml
   *   XML data.
   *
   * @return static
   *   This class.
   */
  public static function createFromXml(\SimpleXMLElement $xml) {
    if ($xml->count() == 0) {
      return NULL;
    }
    $instance = new self();
    $instance->setCaseId((int) $xml['ixBug']);
    $instance->setTitle($xml->sTitle);
    $instance->setIsOpen($xml->fOpen == 'true');
    $instance->setCustomerEmail($xml->sCustomerEmail);
    $instance->setDateOpened(new \DateTime($xml->dtOpened));
    if (!empty($xml->dtClosed)) {
      $instance->setDateClosed(new \DateTime($xml->dtClosed));
    }
    $instance->setStatus($xml->sStatus);
    if (!empty($xml->ixCategory)) {
      $instance->setCategoryId((int) $xml->ixCategory);
      $instance->setCategory($xml->sCategory);
    }
    if (!empty($xml->ixProject)) {
      $instance->setProjectId((int) $xml->ixProject);
      $instance->setProject($xml->sProject);
    }
    if (!empty($xml->ixPriority)) {
      $instance->setPriorityId((int) $xml->ixPriority);
      $instance->setPriority($xml->sPriority);
    }
    if (!empty($xml->ixMailbox)) {
      $instance->setMailboxId((int) $xml->ixMailbox);
    }

    // Custom fields.
    $custom = [];
    $custom['nature'] = (string) $xml->plugin_customfields_at_fogcreek_com_natureg119;
    $custom['alert_status'] = (string) $xml->plugin_customfields_at_fogcreek_com_alertxstatusw51d;
    $custom['alert_header'] = (string) $xml->plugin_customfields_at_fogcreek_com_alertxheaderv51b;
    $instance->setCustomFields($custom);

    $events = [];
    foreach ($xml->events->children() as $event) {
      $events[] = [
        'date' => new \DateTime($event->dt),
        'action' => (string) $event->sVerb,
        'summary' => (string) $event->sHtml,
        'changes' => (string) $event->sChanges,
      ];
    }
    $instance->setEvents($events);

    $tags = [];
    foreach ($xml->tags->children() as $tag) {
      $tags[] = (string) $tag;
    }
    $instance->setTags($tags);

    return $instance;
  }

  /**
   * Get the case ID (ixBug) property.
   */
  public function getCaseId() {
    return $this->id;
  }

  /**
   * Sets the case ID (ixBug) property.
   *
   * @param int $id
   *   The number assigned to a case upon creation.
   *
   * @return static
   *   This class.
   */
  public function setCaseId(int $id) {
    $this->id = $id;
    return $this;
  }

  /**
   * Gets the title property.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets the sTitle property.
   *
   * @param string $title
   *   Case title field.
   *
   * @return static
   *   This class.
   */
  public function setTitle(string $title) {
    $this->title = $title;
    return $this;
  }

  /**
   * Gets the isOpen boolean value.
   */
  public function getIsOpen() {
    return $this->isOpen;
  }

  /**
   * Sets the fOpen property.
   *
   * @param boolean $isOpen
   *   Is open field.
   *
   * @return static
   *   This class.
   */
  public function setIsOpen(bool $isOpen) {
    $this->isOpen = $isOpen;
    return $this;
  }

  /**
   * Gets the cateory id.
   */
  public function getCategoryId() {
    return $this->categoryId;
  }

  /**
   * Sets the ixCategory property.
   *
   * @param int $categoryId
   *   Case category id.
   *
   * @return static
   *   This class.
   */
  public function setCategoryId(int $categoryId) {
    $this->categoryId = $categoryId;
    return $this;
  }

  /**
   * Gets the cateory property.
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * Sets the sCategory property.
   *
   * @param string $category
   *   Case category id.
   *
   * @return static
   *   This class.
   */
  public function setCategory(string $category) {
    $this->category = $category;
    return $this;
  }

  /**
   * Gets the customer email property.
   */
  public function getCustomerEmail() {
    return $this->customerEmail;
  }

  /**
   * Sets the sCustomerEmail property.
   *
   * @param string $customerEmail
   *   Case correspondent field.
   *
   * @return static
   *   This class.
   */
  public function setCustomerEmail(string $customerEmail) {
    $this->customerEmail = $customerEmail;
    return $this;
  }

  /**
   * Gets the events array.
   */
  public function getEvents() {
    return $this->events;
  }

  /**
   * Sets the aEvent property.
   *
   * @param array $events
   *   Array of event details.
   *
   * @return static
   *   This class.
   */
  public function setEvents(array $events) {
    $this->events = $events;
    return $this;
  }

  /**
   * Gets the mailbox id.
   */
  public function getMailboxId() {
    return $this->mailboxId;
  }

  /**
   * Sets the ixMailBox property.
   *
   * @param int $mailbox
   *   Connected Mailbox id.
   *
   * @return static
   *   This class.
   */
  public function setMailboxId(int $mailboxId) {
    $this->mailboxId = $mailboxId;
    return $this;
  }

  /**
   * Gets the priority id.
   */
  public function getPriorityId() {
    return $this->priorityId;
  }

  /**
   * Sets the case priority id (ixPriority) property.
   *
   * @param int $priority
   *   The priority number to assign to a case upon creation.
   *
   * @return static
   *   This class.
   */
  public function setPriorityId(int $priorityId) {
    $this->priorityId = $priorityId;
    return $this;
  }

  /**
   * Gets the priority property.
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * Sets the case priority (sProperty) property.
   *
   * @param string $priority
   *   The priority name.
   *
   * @return static
   *   This class.
   */
  public function setPriority(string $priority) {
    $this->priority = $priority;
    return $this;
  }

  /**
   * Gets the project id.
   */
  public function getProjectId() {
    return $this->projectId;
  }

  /**
   * Sets the ixProject property.
   *
   * @param int $projectId
   *   Case project id.
   *
   * @return static
   *   This class.
   */
  public function setProjectId(int $projectId) {
    $this->projectId = $projectId;
    return $this;
  }

  /**
   * Gets the project property.
   */
  public function getProject() {
    return $this->project;
  }

  /**
   * Sets the sProject property.
   *
   * @param string $project
   *   Case project name.
   *
   * @return static
   *   This class.
   */
  public function setProject(string $project) {
    $this->project = $project;
    return $this;
  }

  /**
   * Gets the tags array.
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * Sets the sTags property.
   *
   * @param array $tags
   *   Case tags field.
   *
   * @return static
   *   This class.
   */
  public function setTags(array $tags) {
    $this->tags = $tags;
    return $this;
  }

  /**
   * Gets the status property.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the sStatus property.
   *
   * @param string $status
   *   Case status field.
   *
   * @return static
   *   This class.
   */
  public function setStatus(string $status) {
    $this->status = $status;
    return $this;
  }

  /**
   * Gets the date opened property.
   */
  public function getdateOpened() {
    return $this->dateOpened;
  }

  /**
   * Sets the dtOpened property.
   *
   * @param \DateTime $dateOpened
   *   Date opened field.
   *
   * @return static
   *   This class.
   */
  public function setDateOpened(\DateTime $dateOpened) {
    $this->dateOpened = $dateOpened;
    return $this;
  }

  /**
   * Gets the date closed property.
   */
  public function getDateClosed() {
    return $this->dateClosed;
  }

  /**
   * Sets the dtClosed property.
   *
   * @param \DateTime $dateClosed
   *   Date Closed field.
   *
   * @return static
   *   This class.
   */
  public function setDateClosed(\DateTime $dateClosed) {
    $this->dateClosed = $dateClosed;
    return $this;
  }

  /**
   * Gets the custom fields array.
   */
  public function getCustomFields() {
    return $this->customFields;
  }

  /**
   * Sets the custom fields.
   *
   * @param array $customFields
   *   Custom fields.
   *
   * @return static
   *   This class.
   */
  public function setCustomFields(array $customFields) {
    $this->customFields = $customFields;
    return $this;
  }

  /**
   * Gets a case summary from the events.
   *
   * @return string
   *   The summary.
   */
  public function getCaseSummary() {
    foreach ($this->getEvents() as $event) {
      if ($event['action'] == 'Opened' && !empty($event['summary'])) {
        return $event['summary'];
      }
    }
  }

  /*
   * Filter events for display.
   *
   * @param array $filter
   *   Array of allowed event actions.
   *
   * @return array
   *   Array of events.
   */
  public function getFilteredEvents(array $filter = NULL) {
    $filter = $filter ?? ['Edited', 'Resolved', 'Reactivated', 'Closed', 'Reopened'];
    $events = [];
    foreach ($this->getEvents() as $event) {
      $isCorrespondentChange = !!preg_match('/Correspondent changed from/', $event['changes']);
      if (in_array($event['action'], $filter) && !$isCorrespondentChange) {
        $events[] = $event;
      }
    }
    return $events;
  }

  /*
   * Get a css class based on the alert status type.
   *
   * @return string
   *   Class name.
   */
  public function getAlertClass() {
    $custom = $this->getCustomFields();
    $status = $custom['alert_status'] ?? NULL;

    switch ($status) {
      case 'Warning':
        return 'warning';
        break;
      case 'Fail':
        return 'danger';
        break;
      case 'Okay':
      default:
        return 'success';
        break;
    }
  }

}
