((window, document) =>
{
    'use strict'

    let trackingStarted = false

    function ctSetCookie(name, value)
    {
        document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; samesite=lax'
    }

    function startTracking()
    {
        if (trackingStarted)
        {
            return
        }
        trackingStarted = true

        const startTime = Date.now()
        const pointerData = []
        let readMouse = true

        ctSetCookie('ct_ps_timestamp', Math.floor(startTime / 1000))
        ctSetCookie('ct_fkp_timestamp', '0')
        ctSetCookie('ct_pointer_data', '0')
        ctSetCookie('ct_timezone', '0')
        setTimeout(() =>
        {
            ctSetCookie('ct_timezone', new Date().getTimezoneOffset() / 60 * (-1))
        }, 1000)

        const readInterval = setInterval(() =>
        {
            readMouse = true
        }, 150)

        const writeInterval = setInterval(() =>
        {
            ctSetCookie('ct_pointer_data', JSON.stringify(pointerData))
        }, 1200)

        function mouseMove(event)
        {
            if (!readMouse)
            {
                return
            }

            pointerData.push([
                Math.round(event.pageY),
                Math.round(event.pageX),
                Math.round(Date.now() - startTime),
            ])
            readMouse = false

            if (pointerData.length >= 100)
            {
                ctSetCookie('ct_pointer_data', JSON.stringify(pointerData))
                window.removeEventListener('mousemove', mouseMove)
                clearInterval(readInterval)
                clearInterval(writeInterval)
            }
        }

        function firstKey()
        {
            ctSetCookie('ct_fkp_timestamp', Math.floor(Date.now() / 1000))
            window.removeEventListener('mousedown', firstKey)
            window.removeEventListener('keydown', firstKey)
        }

        window.addEventListener('mousemove', mouseMove)
        window.addEventListener('mousedown', firstKey)
        window.addEventListener('keydown', firstKey)
    }

    XF.CleanTalkCheckJs = XF.Element.newHandler({
        init()
        {
            // XF 2.0-2.2 uses a jQuery target; XF 2.3 uses a DOM element.
            const target = this.target || this.$target[0]
            target.value = new Date().getFullYear()
            startTracking()
        },
    })

    XF.Element.register('cleantalk-checkjs', 'XF.CleanTalkCheckJs')
})(window, document)
