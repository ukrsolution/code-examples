# get All users for documetns
DELIMITER //
DROP PROCEDURE IF EXISTS get_visible_users_by_therapist_for_document//
CREATE PROCEDURE get_visible_users_by_therapist_for_document(IN _therapist_user_id INT)
BEGIN

  /* contacts for messages */
  SET @therapist_user_id = _therapist_user_id;
  SET @therapist_id = 0;
  SET @supervisitor_muse_team_id = 0;
  SET @is_supervisor_users_options_enabled = 'defalut';
  SET @is_system_developer_users_options_enabled = 'defalut';
  #get therapist user id

  SELECT id_self
  FROM `users`
  WHERE `id` = @therapist_user_id 
  AND `id_role` != 4
  INTO @therapist_id;
  
  # get supervisor muse team id
  SELECT STA.`id_supervisor` 
  FROM`supervisor_therapist_assignements` AS STA 
  WHERE STA.`id_therapist` = @therapist_id
  ORDER BY STA.`id` DESC
  LIMIT 1
  INTO @supervisitor_muse_team_id;
  # get supervisor access to viewable staff and clients for this therapist
  SELECT users_options
  FROM user_functions_access AS UFA
  WHERE user_id = @supervisitor_muse_team_id
  LIMIT 1
  INTO @is_supervisor_users_options_enabled;

  #IF NOT 0 so mast be 1
  IF STRCMP(@is_supervisor_users_options_enabled, 0) != 0 THEN
    SET @is_supervisor_users_options_enabled = 1;
  END IF;

  # get System developer access to viewable user option for company
  SELECT UFA.`users_options`
    FROM user_functions_access AS UFA,
         users AS U
   WHERE @company_id = U.`company_id`
     AND U.`id_role` = 7
     AND UFA.`real_user_id` = U.`id`
   LIMIT 1
    INTO @is_system_developer_users_options_enabled;

  #IF NOT 0 so mast be 1
  IF (STRCMP(@is_system_developer_users_options_enabled, 0)) != 0 AND @supervisitor_muse_team_id != 0 THEN
    SET @is_system_developer_users_options_enabled = 1;
  END IF;

  SELECT * FROM (
    SELECT U.`id` AS 'user_id',
      MT.`id` AS 'muse_team_id',
      NULL AS 'patient_id',
      MT.`email` AS 'email',
      CONCAT(MT.`first_name`,' ',MT.`middle_name`,' ',MT.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(MT.`biller` = 1, "Biller", R.`name`) AS 'role_name',
      "My supervisor Link" AS link_type
    FROM `muse_team` AS MT
    LEFT JOIN `users` AS U
    ON U.`id_self` = MT.`id` AND U.`id_role` != 4
    LEFT JOIN `roles` AS R
    ON R.`id` = U.`id_role`
    WHERE MT.`id` IN (@supervisitor_muse_team_id)
      AND @supervisitor_muse_team_id != 0

    UNION
    # get admins
    SELECT U.`id` AS 'user_id',
      MT.`id` AS 'muse_team_id',
      NULL AS 'patient_id',
      MT.`email` AS 'email',
      CONCAT(MT.`first_name`,' ',MT.`middle_name`,' ',MT.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(MT.`biller` = 1, "Biller", R.`name`) AS 'role_name',
      "supervisor_admin_assignments Link" AS link_type
    FROM `muse_team` AS MT
    LEFT JOIN `users` AS U
    ON U.`id_self` = MT.`id` AND U.`id_role` != 4
    LEFT JOIN `roles` AS R
    ON R.`id` = U.`id_role`
    WHERE FIND_IN_SET(MT.`id`, (
        SELECT GROUP_CONCAT(saa.`id_admin`)
        FROM `supervisor_admin_assignments` AS saa ,
             `muse_team` AS mt
        WHERE saa.`id_supervisor` = @supervisitor_muse_team_id
        AND saa.`is_active` = 1
        AND mt.`id` = saa.`id_admin`
        AND mt.`biller` = 0
        AND @supervisitor_muse_team_id != 0
          )
      )

    UNION    
    # get clients by therapist
    SELECT U.`id` AS 'user_id',
      NULL AS 'muse_team_id',
      P.`id` AS 'patient_id',
      P.`email` AS 'email',
      CONCAT(P.`first_name`,' ',P.`middle_name`,' ',P.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(P.`is_carecoordinator` = 1, "Care Coordinator", IF(P.`is_collaborator` = 1, "Collaborator", R.`name`)) AS 'role_name',
      "therapist_patient_assignements Link" AS link_type
    FROM `patient` AS P
    LEFT JOIN `users` AS U
    ON U.`id_self` = P.`id` AND U.`id_role` = 4
    LEFT JOIN `roles` AS R
    ON R.`id` = U.`id_role`
    WHERE 1=1
    AND U.`id` IS NOT NULL
    AND FIND_IN_SET(P.`id`, (
      SELECT GROUP_CONCAT(TPA.`id_patient`)
      FROM `therapist_patient_assignements` AS TPA
      WHERE FIND_IN_SET(TPA.`id_therapist`, @therapist_id)
      )
    )
    AND (SELECT _bps.`id_status` 
      FROM `bridge_patient_status` AS _bps 
      WHERE _bps.`id_patient` = P.`id` 
      ORDER BY _bps.`datetime` DESC 
      LIMIT 1) NOT IN (3,5,7)

    UNION
    
    # get collaborators
    SELECT
      U.`id` AS 'user_id',
      NULL AS 'muse_team_id',
      CA.`id_collaborator` AS 'patient_id',
      P.`email` AS 'email',
      CONCAT(P.`first_name`,' ',P.`middle_name`,' ',P.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(P.`is_carecoordinator` = 1, "Care Coordinator", IF(P.`is_collaborator` = 1, "Collaborator", "Patient")) AS 'role_name',
      "collaborator_assignments Link" AS link_type

    FROM
      `collaborator_assignments` AS CA,
      `users` AS U,
      `patient` AS P
    WHERE
    (
        FIND_IN_SET(CA.`id_assigner`, @therapist_id)
      OR
        FIND_IN_SET(CA.`id_therapist`, @therapist_id)
    )
    AND U.`id_role` = 4
    AND U.`id_self` = CA.`id_collaborator`
    AND P.`id` = CA.`id_collaborator`

    AND (SELECT _bps.`id_status` 
      FROM `bridge_patient_status` AS _bps 
      WHERE _bps.`id_patient` = P.`id` 
      ORDER BY _bps.`datetime` DESC 
      LIMIT 1) NOT IN (3,5,7)
    
    UNION

    # get clients from user options
    SELECT
      U.`id` AS 'user_id',
      MT.`id` AS 'muse_team_id',
      NULL AS 'patient_id',
      MT.`email` AS 'email',
      CONCAT(MT.`first_name`,' ',MT.`middle_name`,' ',MT.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(MT.`biller` = 1, "Biller", R.`name`) AS 'role_name',
      "options_to_users muse_team Link" AS link_type
    FROM
      options_to_users AS OTU,
      users AS U,
      muse_team AS MT,
      roles AS R
    WHERE OTU.`user_id` IN(@therapist_user_id)
      AND OTU.`option_id` = 1/* sharing document */
      AND OTU.`option_value` = 'Y'
      AND OTU.`user_option_id` = U.`id`
      AND U.`id_role` != 4
      AND U.`id_self` = MT.`id`
      AND U.`id_role` = R.`id`
      AND @is_supervisor_users_options_enabled = 1
      AND @is_system_developer_users_options_enabled = 1

    UNION
    # get staff from user options
    SELECT
      U.`id` AS 'user_id',
      NULL AS 'muse_team_id',
      P.`id` AS 'patient_id',
      P.`email` AS 'email',
      CONCAT(P.`first_name`,' ',P.`middle_name`,' ',P.`last_name`) AS 'full_name',
      U.`id_role` AS 'role_id',
      IF(P.`is_carecoordinator` = 1, "Care Coordinator", IF(P.`is_collaborator` = 1, "Collaborator", R.`name`)) AS 'role_name',
      "options_to_users clients Link" AS link_type
    FROM
      options_to_users AS OTU,
      users AS U,
      patient AS P,
      roles AS R
    WHERE OTU.`user_id` IN(@therapist_user_id)
      AND OTU.`option_id` = 1/* sharing document */
      AND OTU.`option_value` = 'Y'
      AND OTU.`user_option_id` = U.`id`
      AND U.`id_role` = 4
      AND U.`id_self` = P.`id`
      AND U.`id_role` = R.`id`
      AND @is_supervisor_users_options_enabled = 1
      AND @is_system_developer_users_options_enabled = 1
      AND (SELECT _bps.`id_status` 
      FROM `bridge_patient_status` AS _bps 
      WHERE _bps.`id_patient` = P.`id` 
      ORDER BY _bps.`datetime` DESC 
      LIMIT 1) NOT IN (3,5,7)
      
    ) AS tbl
    GROUP BY user_id
    ORDER BY full_name;

END//