<?php

namespace Cleantalk\Custom;

class LicenseBanner
{
    const OPTION_ID = 'ct_license_notice';
    const COOKIE_NAME = 'ct_license_banner_dismissed';
    const PRODUCT_ID = 1;
    const PAYMENT_URL = 'https://p.cleantalk.org/';

    /**
     * Persist notice_paid_till flags used to render admin banners.
     *
     * @param array $npt_result
     * @param bool $key_is_ok
     * @return void
     */
    public static function saveFromNoticePaidTill($npt_result, $key_is_ok)
    {
        $data = self::defaultData();

        // Keep trial/renew flags even when moderate=0 (expired trial is still a valid key).
        $can_save = is_array($npt_result)
            && empty($npt_result['error'])
            && empty($npt_result['error_message'])
            && !empty($npt_result['valid']);
        if ( $can_save || $key_is_ok ) {
            $data['valid'] = !empty($npt_result['valid']) ? 1 : 0;
            $data['moderate'] = isset($npt_result['moderate']) ? (int)$npt_result['moderate'] : 0;
            $data['show_notice'] = !empty($npt_result['show_notice']) ? 1 : 0;
            $data['renew'] = !empty($npt_result['renew']) ? 1 : 0;
            $data['trial'] = !empty($npt_result['trial']) ? 1 : 0;
            $data['user_token'] = isset($npt_result['user_token']) ? (string)$npt_result['user_token'] : '';
        }

        $data['last_check'] = time();
        self::save($data);
    }

    /**
     * @return void
     */
    public static function clear()
    {
        self::save(self::defaultData());
    }

    /**
     * @return string|null 'trial'|'renew'|null
     */
    public static function getType()
    {
        $data = self::getData();
        if ( empty($data['valid']) || empty($data['show_notice']) ) {
            return null;
        }
        if ( !empty($data['trial']) ) {
            return 'trial';
        }
        if ( !empty($data['renew']) ) {
            return 'renew';
        }

        return null;
    }

    /**
     * Addon settings stay visible after dismiss on other ACP pages.
     *
     * @return bool
     */
    public static function isSettingsPage()
    {
        $haystack = '';
        if ( isset($_SERVER['REQUEST_URI']) ) {
            $haystack .= (string)$_SERVER['REQUEST_URI'];
        }
        if ( isset($_SERVER['QUERY_STRING']) ) {
            $haystack .= '&' . (string)$_SERVER['QUERY_STRING'];
        }

        return (
            stripos($haystack, 'add-ons/CleanTalk/options') !== false
            || stripos($haystack, 'options/groups/cleantalk') !== false
        );
    }

    /**
     * @param bool|null $is_settings_page
     * @return bool
     */
    public static function shouldShow($is_settings_page = null)
    {
        if ( !self::getType() ) {
            return false;
        }

        $visitor = \XF::visitor();
        if ( empty($visitor->user_id) || empty($visitor->is_admin) ) {
            return false;
        }

        if ( $is_settings_page === null ) {
            $is_settings_page = self::isSettingsPage();
        }

        if ( $is_settings_page ) {
            return true;
        }

        $type = self::getType();
        $dismissed = isset($_COOKIE[self::COOKIE_NAME]) ? (string)$_COOKIE[self::COOKIE_NAME] : '';

        return $dismissed !== $type;
    }

    /**
     * Small renew/trial banner HTML.
     *
     * @param bool $is_settings_page
     * @return string
     */
    public static function render($is_settings_page)
    {
        $type = self::getType();
        if ( !$type || !self::shouldShow($is_settings_page) ) {
            return '';
        }

        $data = self::getData();
        $is_trial = ($type === 'trial');
        $title = $is_trial
            ? 'Please upgrade your license to keep your site protected!'
            : 'Please renew your license to keep your site protected!';
        $button = $is_trial ? 'UPGRADE NOW' : 'RENEW NOW';
        $utm_content = $is_trial ? 'renew_notice_trial' : 'renew_notice_renew';
        $payment_url = self::buildPaymentUrl($data['user_token'], $utm_content);

        $title = self::esc($title);
        $button = self::esc($button);
        $payment_url = self::esc($payment_url);
        $dismiss_attr = $is_settings_page ? 'hidden' : '';

        return <<<HTML
<style>
.ct-license-banner{box-sizing:border-box;display:flex;align-items:center;justify-content:space-between;gap:20px;margin:0 0 16px;padding:16px 20px;background:#fff7f6;border:1px solid #f0c9c6;border-left:6px solid #d93025;border-radius:10px;box-shadow:0 8px 24px rgba(64,72,79,.08);font-family:Inter,Arial,sans-serif;color:#1f2328}
.ct-license-banner *,.ct-license-banner *::before,.ct-license-banner *::after{box-sizing:border-box}
.ct-license-banner__main{min-width:0;flex:1}
.ct-license-banner__brand{display:flex;align-items:center;gap:8px;margin:0 0 6px;font-size:13px;font-weight:600;color:#40484f}
.ct-license-banner__logo{width:22px;height:22px;border-radius:50%;background:#1f8a70;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.ct-license-banner__sep{opacity:.45}
.ct-license-banner__title{margin:0 0 4px;font-size:16px;line-height:1.35;font-weight:700}
.ct-license-banner__stats{margin:0 0 4px;font-size:12px;line-height:1.4;color:#66707a}
.ct-license-banner__hint{margin:0;font-size:11px;line-height:1.4;color:#8b949e}
.ct-license-banner__actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
.ct-license-banner__btn{display:inline-block;padding:10px 18px;border-radius:999px;background:#111827;color:#fff!important;text-decoration:none!important;font-size:13px;font-weight:700;letter-spacing:.02em;white-space:nowrap}
.ct-license-banner__btn:hover{background:#000;color:#fff!important}
.ct-license-banner__close{appearance:none;border:0;background:transparent;color:#8b949e;font-size:22px;line-height:1;cursor:pointer;padding:0 2px}
.ct-license-banner__close[hidden]{display:none}
@media (max-width:720px){.ct-license-banner{flex-direction:column;align-items:flex-start}.ct-license-banner__actions{width:100%;justify-content:space-between}}
</style>
<div class="ct-license-banner" data-ct-license-banner="{$type}" role="status">
    <div class="ct-license-banner__main">
        <div class="ct-license-banner__brand">
            <span class="ct-license-banner__logo">C</span>
            <span>CleanTalk</span>
            <span class="ct-license-banner__sep">|</span>
            <span>Anti-Spam</span>
        </div>
        <p class="ct-license-banner__title">{$title}</p>
        <p class="ct-license-banner__stats">Trusted by 1,079,000+ sites | 12,450,238,000+ spam blocked | 99.9982% spam detection accuracy</p>
        <p class="ct-license-banner__hint">Account status updates every day or after you save Antispam by CleanTalk settings.</p>
    </div>
    <div class="ct-license-banner__actions">
        <a class="ct-license-banner__btn" href="{$payment_url}" target="_blank" rel="noopener noreferrer">{$button}</a>
        <button type="button" class="ct-license-banner__close" aria-label="Close" {$dismiss_attr}>&times;</button>
    </div>
</div>
<script>
(function(){
    var banner = document.querySelector('[data-ct-license-banner]');
    if (!banner) return;
    var closeBtn = banner.querySelector('.ct-license-banner__close');
    if (!closeBtn || closeBtn.hidden) return;
    closeBtn.addEventListener('click', function(){
        document.cookie = 'ct_license_banner_dismissed=' + encodeURIComponent(banner.getAttribute('data-ct-license-banner') || '') + '; path=/; max-age=604800; samesite=lax';
        banner.parentNode && banner.parentNode.removeChild(banner);
    });
})();
</script>
HTML;
    }

    /**
     * @return array
     */
    public static function getData()
    {
        $raw = Funcs::getXF()->options()->ct_license_notice;
        $data = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : array());

        return array_merge(self::defaultData(), is_array($data) ? $data : array());
    }

    /**
     * @return array
     */
    private static function defaultData()
    {
        return array(
            'valid' => 0,
            'moderate' => 0,
            'show_notice' => 0,
            'renew' => 0,
            'trial' => 0,
            'user_token' => '',
            'last_check' => 0,
        );
    }

    /**
     * @param array $data
     * @return void
     */
    private static function save($data)
    {
        Funcs::getXF()->repository('XF:Option')->updateOption(self::OPTION_ID, json_encode($data));
    }

    /**
     * @param string $user_token
     * @param string $utm_content
     * @return string
     */
    private static function buildPaymentUrl($user_token, $utm_content)
    {
        return self::PAYMENT_URL . '?' . http_build_query(array(
            'product_id' => self::PRODUCT_ID,
            'user_token' => $user_token,
            'utm_term' => 'payment',
            'utm_source' => 'admin_panel',
            'utm_medium' => 'xenforo',
            'utm_content' => $utm_content,
            'utm_campaign' => 'xf_antispam_links',
        ));
    }

    /**
     * @param string $value
     * @return string
     */
    private static function esc($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
