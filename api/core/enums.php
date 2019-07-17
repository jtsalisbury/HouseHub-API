<?php

    /*
        Includes all enumerations for errors, messages, etc;
    */

    class ENUMS {
        const SUCCESS = "success";

        const TOKEN_INVALID = "invalid_request_token";
        const FIELD_NOT_SET = "fields_not_set";
        const PASS_NOT_EQUAL = "password_not_equal";
        const DB_NOT_CONNECTED = "database_not_connected";
        const FAILED_NEW_USER = "failed_insert_user";
        const INSERT_USER_EXISTS = "failed_insert_user_exists";

        const USER_NOT_EXIST = "user_does_not_exist";
        const FIELD_NOT_EXIST = "user_field_does_not_exist";
        const UPDATE_PASS_NOT_EQUAL = "update_user_new_pass_not_equal";

        const GENERAL_INSERT_ERROR = "general_insert_error";
        const DUPLICATE_INSERT_TITLE = "title_already_exists";
        const FIELD_TYPE_WRONG = "field_incorrect_type";
        const FIELD_TYPE_POSITIVE = "field_postives_only";

        const INVALID_NUM_IMAGES = "invalid_number_pictures";
        const INVALID_FILE_TYPE = "invalid_file_type_supplied";
        const IMAGE_TOO_LARGE = "image_too_large";
        const FILE_MOVE_ERROR = "error_move_file";
    }

?>