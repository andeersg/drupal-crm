<?php

/**
 * @file
 * Defines channel entity controller.
 */

/**
 * Class CRMChannelController
 */
class CRMChannelController extends EntityAPIController {

  /**
   * Overrides EntityAPIController::buildQuery().
   */
  public function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    // Add an alias to this query to ensure that we can tell if this is
    // the current revision or not.
    $query = parent::buildQuery($ids, $conditions, $revision_id);
    $query->addField('base', $this->revisionKey, 'current_revision_id');
    return $query;
  }

  /**
   * Overrides EntityAPIController::access().
   */
  public function access($op, $entity = NULL, $account = NULL) {
    if ($op !== 'create' && !$entity) {
      return FALSE;
    }
    if (is_object($entity) && !crm_channel_type_get_name($entity->type)) {
      return FALSE;
    }
    // The administer permission is a blanket override.
    if (user_access('crm bypass access')) {
      return TRUE;
    }
    switch ($op) {
      case 'create':
        return user_access('crm channel create');
      case 'view':
        return user_access('crm channel view');
      case 'update':
        return user_access('crm channel update');
      case 'delete':
        return user_access('crm channel delete');
    }
    return FALSE;
  }

  /**
   * Overrides EntityAPIController::create().
   */
  public function create(array $values = array()) {
    $values += array(
      'status' => CRM_CHANNEL_STATUS_REGISTERED,
    );
    return parent::create($values);
  }

  /**
   * Overrides EntityAPIController::save().
   */
  public function save($entity) {

    // If we are going to save new channel data needs to be validated against
    // uniqueness.
    if (isset($entity->is_new) && $entity->is_new == TRUE && !isset($entity->order_id)) {
      // Throw exception if new but not unique channel is going to be save.
      if (!crm_channel_is_unique($entity->type, $entity)) {
        throw new Exception('Channel already exists');
      }
      // Add timestamp.
      $entity->created = REQUEST_TIME;
    }
    // Set changed time arbitrary.
    $entity->changed = REQUEST_TIME;

    // Create new revision by default.
    if (!isset($entity->revision)) {
      $entity->revision = TRUE;
    }

    if (!isset($entity->preserve_token)) {
      // Reset token on channel save.
      $entity->token = NULL;
      $entity->token_created = 0;
    }

    // Save channel.
    $return = parent::save($entity);
    if (isset($entity->debug) && $entity->debug == TRUE) {
      $e = new Exception();
      watchdog('crm_channel', 'Channel (channel: @id) has been saved. Trace: <pre>@debug</pre>', array('@id' => $entity->channel_id, '@debug' => $e->getTraceAsString()), WATCHDOG_NOTICE);
    }
    else {
      watchdog('crm_channel', 'Channel (channel: @id) has been saved.', array('@id' => $entity->channel_id), WATCHDOG_NOTICE);
    }
    return $return;
  }

  /**
   * Overrides DrupalDefaultEntityController::saveRevision().
   */
  protected function saveRevision($entity) {
    $entity->revision_timestamp = REQUEST_TIME;
    $entity->revision_uid = $GLOBALS['user']->uid;
    return parent::saveRevision($entity);
  }

}
