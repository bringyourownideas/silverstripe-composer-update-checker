<?php

  /**
   * Describes an available update to an installed Composer Package
   *
   * Used to ensure the same update is not notified multiple times
   */
  class ComposerUpdate extends DataObject {

    #region Declarations

    static $db = array(
      'Name' => 'Varchar(255)',
      'Installed' => 'Varchar(255)',
      'Available' => 'Varchar(255)'
    );

    #endregion Declarations

  }
