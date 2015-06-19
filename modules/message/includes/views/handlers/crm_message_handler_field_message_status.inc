<?php

/**
 * Field handler to translate a channel type into its readable form.
 */
class crm_message_handler_field_message_status extends crm_message_handler_field_message {
  function option_definition() {
    $options = parent::option_definition();

    $options['use_raw_value'] = array('default' => FALSE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['use_raw_value'] = array(
      '#title' => t('Use raw value'),
      '#description' => t('Check if you want to display the raw value instead of the human readable value.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['use_raw_value']),
    );
  }

  function render($values) {
    if ($status = $this->get_value($values)) {
      // Return the raw value if specified.
      if (!empty($this->options['use_raw_value'])) {
        return $this->sanitize_value($status);
      }

      $value = crm_message_status_get_name($status);
      return $this->render_link($this->sanitize_value($value), $values);
    }

    return '';
  }
}