<?php
/*
 * ----------------------------------------------------------------------
 *
 *                          Borlabs Cookie
 *                      developed by Borlabs
 *
 * ----------------------------------------------------------------------
 *
 * Copyright 2018-2021 Borlabs - Benjamin A. Bornschein. All rights reserved.
 * This file may not be redistributed in whole or significant part.
 * Content of this file is protected by international copyright laws.
 *
 * ----------------- Borlabs Cookie IS NOT FREE SOFTWARE -----------------
 *
 * @copyright Borlabs - Benjamin A. Bornschein, https://borlabs.io
 * @author Benjamin A. Bornschein, Borlabs ben@borlabs.io
 *
 */

namespace BorlabsCookie\Cookie\Frontend;

use BorlabsCookie\Cookie\Config;

class ConsentLog
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function __construct()
    {
    }

    public function __clone()
    {
        trigger_error('Cloning is not allowed.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserialize is forbidden.', E_USER_ERROR);
    }

    /**
     * add function.
     *
     * @access public
     *
     * @param  mixed  $cookieData
     *
     * @return void
     */
    public function add($cookieData, $language)
    {
        global $wpdb;

        $allowedCookieGroups = [];
        $allowedCookies = [];
        $consents = [];

        $table = (Config::getInstance()->get('aggregateCookieConsent') ? $wpdb->base_prefix : $wpdb->prefix)
            . "borlabs_cookie_consent_log";

        // Validate cookie data
        if (! empty($cookieData['uid'])) {
            // Validate uid
            if (preg_match('/[0-9a-z]{8}\-[0-9a-z]{8}\-[0-9a-z]{8}\-[0-9a-z]{8}/', $cookieData['uid'])) {
                // Sanitize language
                $language = strtolower(preg_replace('/[^a-z\-_]+/', '', $language));

                // Get all valid cookie group ids
                $allowedCookieGroups = Cookies::getInstance()->getAllCookieGroupsOfLanguage($language);
                $allowedCookies = Cookies::getInstance()->getAllCookiesOfLanguage($language);

                // Validate consents
                if (! empty($cookieData['consents'])) {
                    foreach ($cookieData['consents'] as $cookieGroup => $cookies) {
                        if (! empty($allowedCookieGroups[$cookieGroup])) {
                            $consents[$cookieGroup] = [];

                            if (! empty($cookies)) {
                                foreach ($cookies as $cookie) {
                                    if (! empty($allowedCookies[$cookie])) {
                                        $consents[$cookieGroup][] = $cookie;
                                    }
                                }
                            }
                        }
                    }
                }

                // Get last log
                $lastLog = $wpdb->get_results(
                    "
                    SELECT
                        `cookie_version`,
                        `consents`
                    FROM
                        `" . $table . "`
                    WHERE
                        `uid` = '" . esc_sql($cookieData['uid']) . "'
                        AND
                        `is_latest` = 1
                "
                );

                $cookieVersion = null;

                if (! empty($cookieData['version'])) {
                    $cookieVersion = intval($cookieData['version']);
                } else {
                    $cookieVersion = intval(get_site_option('BorlabsCookieCookieVersion', 1));
                }

                $consents = serialize($consents);

                if (
                    empty($lastLog[0]->consents)
                    || ($lastLog[0]->consents !== $consents
                        && $lastLog[0]->cookie_version !== $cookieVersion)
                ) {
                    if (! empty($lastLog[0]->consents)) {
                        // Set "is_latest" of all old entries of the uid to 0
                        $wpdb->query(
                            "
                            UPDATE
                                `" . $table . "`
                            SET
                                `is_latest` = 0
                            WHERE
                                `uid` = '" . esc_sql($cookieData['uid']) . "'
                        "
                        );
                    }

                    // Insert log
                    $wpdb->query(
                        "
                        INSERT INTO
                            `" . $table . "`
                        (
                            `log_id`,
                            `uid`,
                            `cookie_version`,
                            `consents`,
                            `is_latest`,
                            `stamp`
                        )
                        VALUES
                        (
                            null,
                            '" . esc_sql($cookieData['uid']) . "',
                            '" . $cookieVersion . "',
                            '" . esc_sql($consents) . "',
                            '1',
                            NOW()
                        )
                    "
                    );
                }
            }
        }

        return true;
    }

    /**
     * getHistory function.
     *
     * @access public
     *
     * @param  mixed  $uid
     * @param  mixed  $language
     *
     * @return void
     */
    public function getConsentHistory($uid, $language)
    {
        global $wpdb;

        $consentHistory = [];

        $uid = trim(strtolower($uid));

        $table = (Config::getInstance()->get('aggregateCookieConsent') ? $wpdb->base_prefix : $wpdb->prefix)
            . "borlabs_cookie_consent_log";

        if (preg_match('/[0-9a-z]{8}\-[0-9a-z]{8}\-[0-9a-z]{8}\-[0-9a-z]{8}/', $uid)) {
            // Sanitize language
            $language = strtoupper(preg_replace('/[^a-z\-_]+/', '', $language));

            $availableCookieGroups = Cookies::getInstance()->getAllCookieGroupsOfLanguage($language);
            $availableCookies = Cookies::getInstance()->getAllCookiesOfLanguage($language);

            $logs = $wpdb->get_results(
                "
                SELECT
                    `log_id`,
                    `cookie_version`,
                    `consents`,
                    `stamp`
                FROM
                    `" . $table . "`
                WHERE
                    `uid` = '" . esc_sql($uid) . "'
                ORDER BY
                    `stamp` DESC
            "
            );

            foreach ($logs as $logItem) {
                $consentsTranslated = [];
                $finalConsentList = [];

                $consents = unserialize($logItem->consents);

                if (! empty($consents)) {
                    foreach ($consents as $cookieGroup => $cookies) {
                        if (! empty($availableCookieGroups[$cookieGroup])) {
                            $consentsTranslated[$cookieGroup]['cookieGroup'] = $availableCookieGroups[$cookieGroup];

                            if (! empty($cookies)) {
                                foreach ($cookies as $cookie) {
                                    if (! empty($availableCookies[$cookie])) {
                                        $consentsTranslated[$cookieGroup]['cookies'][] = $availableCookies[$cookie];
                                    }
                                }
                            }
                        }
                    }

                    foreach ($consentsTranslated as $data) {
                        $finalConsentList[] = $data['cookieGroup'] . (! empty($data['cookies']) ? ': ' . implode(
                                    ', ',
                                    $data['cookies']
                                ) : '');
                    }
                }

                $consentHistory[] = [
                    'version' => $logItem->cookie_version,
                    'consent' => implode('<br>', $finalConsentList),
                    'stamp' => $logItem->stamp,
                ];
            }
        }

        return $consentHistory;
    }
}
