/**
 * Main JS for TribeOS' AdShield
 * Requires : Jquery 2.x+
 *
 * UserKey : a unique user key for each website they register to TribeOS?
 */
AdShield = function()
{

    var self = this;
    self.urls = {};
    self.UserKey = null;
    self.AdShieldType = 3;

    var GetParameterByName = function(name, url)
    {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    var httpGet = function(url, arg, callback)
    {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                callback(this.responseText.toJSON());
            }
        };
        xhttp.open("GET", url, true);
        var data = JSON.stringify(arg);
        xhttp.send(data);
    }

    var httpPost = function(url, arg, callback)
    {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                callback(this.responseText.toJSON());
            }
        };
        xhttp.setRequestHeader("Content-Type", "application/json");
        xhttp.open("POST", url, true);
        var data = JSON.stringify(arg);
        xhttp.send(data);
    }

    /**
     * sends stat of user to tribeos
     * @param {[type]} status [description]
     * route('logstat')
     */
    self.SendStatLog = function(status)
    {
        //send stat log for iframe
        var refererUrl = document.referrer;
        var fullUrl = encodeURIComponent(refererUrl);
        if (refererUrl.indexOf("://") > -1) { domain = refererUrl.split('/')[2]; }
        else { domain = refererUrl.split('/')[0]; }
        domain = domain.split(':')[0];
        var visitUrl = document.location.toString() || "";
        var source = GetParameterByName("utm_source");
        var subsource = GetParameterByName("utm_medium");
        var arg = {
            key : self.UserKey,
            url : domain, full_url : fullUrl, status : status,
            source : source, sub_source : subsource,
            user_agent : navigator.userAgent, visitUrl : visitUrl
        }
        $.post(self.urls.statlog, arg, function(d) {});
    }

    /**
     * log and check if site is being run under an Iframe
     */
    self.CheckIframed = function()
    {
        if (window.top != window.self)
        {
            $('a').click(function() {
                window.open($(this).attr("href")); return false;
            });
            self.SendStatLog(5);
            return true;
        }
        return false;
    }

    /**
     * for performing referrer url check and logging it
     * @param  {[type]} url [description]
     * @return {[type]}     [description]
     * route('checker')
     */
    self.CheckReferrerUrl = function()
    {
        var refererUrl = document.referrer;

        if (refererUrl == "")
        {
            if (window.top == window.self) self.SendStatLog(7);
            return;
        }

        var fullUrl = encodeURIComponent(document.referrer);
        if (refererUrl.indexOf("://") > -1) {
            domain = refererUrl.split('/')[2];
        } else {
            domain = refererUrl.split('/')[0];
        }
        domain = domain.split(':')[0];

        var source = GetParameterByName("utm_source");
        var subsource = GetParameterByName("utm_medium");

        var arg = {
            key : self.UserKey,
            url : domain, fullUrl : fullUrl,
            source : source, sub_source : subsource,
            user_agent : navigator.userAgent
        }

        //TODO : what to do for each conditions here? or leave them as is?
        $.post(self.urls.checkReferrer, arg, function(d) {
            switch(d.result) {
                case "{{ ReferrerFilterController::STATUS_SAFE }}" :
                    //safe - redirect to share.cat
                    // window.location = "http://share.cat" + window.location.pathname;
                    break;
                case "{{ ReferrerFilterController::STATUS_UNKNOWN }}" :
                    //unknown - don't redirect
                    break;
                case "{{ ReferrerFilterController::STATUS_UNSAFE }}" :
                    //get source and subsource
                    //unsafe - redirect to pub
                    // window.location = "http://track.content-feed.com/click?offer_id=492570&aff_id=1001";
                    break;
                default:
            }
        }, 'json');
    }



    /**
     * ADSHIELD
     * for performing IP check and start adshield
     * @type {Number}
     */
    var grecaptchaId = null;
    self.StartAdShield = function()
    {
        var adshield_ads = "ins.adsbygoogle ins ins, div.advertiseAd, div.redirectAd, div[id^='rcjsload'], div.zergnetAd,iframe[src*='yieldtraffic.com']";
        var shieldType = self.AdShieldType;
        if (typeof shieldType != 'undefined') {
            if (shieldType == "0") {
                //disable ad shield
                return;
            } else {
                shield_type = shieldType;
            }
        }

        var adShieldID = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        if (shield_type == "1") {
            //cover all ads with blocker
            var ads = adshield_ads;
            self.ActivateShieldForAds(adShieldID, ads);
        } else if (shield_type == "2") {
            var shield = $(document.createElement('div'));
            shield.css({ position: "fixed", top: 0, left: 0, right: 0, bottom: 0, background: "#000", opacity: "0.0" });
            shield.attr("id", adShieldID);
            $("body").append(shield);
        } else if (shield_type == "3") {
            //ad click handler
            self.ClickedMyAd.init({
                selectors : adshield_ads,
                onClick : function() {
                    try {
                        fbq('track', 'AdClick'); //facebook tracking
                    } catch (e) {}

                    try {
                        dataLayer.push({'event':'Adsense Click'}); //google tag mgr. tracking (manual trigger)
                    } catch (e) { }

                    self.LogAdShieldClick(function(logid, status) {
                        if (status === 0) window.location = ""; //set a URL here to redirect users to when they are blacklisted
                    }, {
                        ad_type : self.ClickedMyAd.adType,
                        status : self.ClickedMyAd.statusValue
                    });
                }
            });
            self.ClickedMyAd.statusValue = 0;
        } else {
            return;
        }

        //if check return false, leave captcha as is
        //when user clicks blocker, launch captcha
        self.SetAdshieldOnClick(adShieldID, shield_type);

        //perform ip check
        $.post(self.urls.adShieldHandler, { checkip : 1, key : self.UserKey }, function(d)
        {
            switch(d.status)
            {
                case "1":
                    self.ClickedMyAd.statusValue = 1;
                    //whitelisted
                    $("div#" + adShieldID).remove();
                    break;
                case "2":
                    //greylist
                    if (shield_type == "3")
                    {
                        var ads = adshield_ads;
                        self.ActivateShieldForAds(adShieldID, ads);
                        self.SetAdshieldOnClick(adShieldID, shield_type);
                        self.ClickedMyAd.statusValue = 0;
                        self.ClickedMyAd.setClickCatch(true);
                        self.ClickedMyAd.lateShielding = function(lateAds)
                        {
                            self.ActivateShieldForAds(adShieldID, lateAds);
                            self.SetAdshieldOnClick(adShieldID, 3);
                        }
                    }
                    break;
                case "0":
                    //redirect
                    // window.location = "http://share.cat/sys/block.html";
                    window.location = "http://redirect.com";
                    break;
            }
        }, 'json');

    }

    /**
     * sets the click/mouse event capture for the AdShields div's
     * @param {[type]} adShieldID       [description]
     * @param {[type]} shield_type [description]
     * @param {[type]} logUrl      [description]
     */
    self.SetAdshieldOnClick = function(adShieldID, shield_type)
    {
        $("body").off("click", "div#" + adShieldID);
        $("div#" + adShieldID).off("click");
        $("div#" + adShieldID).click(function() {
            // self.RenderCaptcha(1, adShieldID);
            //log ad click
            self.LogAdShieldClick(function(logid, status)
            {
                // if (status === 0) window.location = "http://share.cat/sys/block.html";
                if (status === 0) window.location = "http://redirect.com";
                //initialize recaptcha
                if (shield_type == "3")
                {
                    // self.RenderCaptcha(logid, adShieldID, function()
                    // {
                        self.ClickedMyAd.setClickCatch(false);
                        self.ClickedMyAd.statusValue = 1;
                    // });
                }
                else
                {
                    // self.RenderCaptcha(logid, adShieldID);
                }
            });
        });
    }


    // self.RenderCaptcha = function(savedLogId, adShieldID, onSuccess)
    // {

    //     var captchaStatus = 'none';

    //     var onCaptchaHide = function()
    //     {
    //         if (captchaStatus == 'none') self.LogCaptcha(savedLogId, 3);;
    //         grecaptcha.reset();
    //     }

    //     if (grecaptchaId !== null)
    //     {
    //         //resets existing recaptcha
    //         grecaptcha.reset(grecaptchaId);
    //     } 
    //     else
    //     {
    //         grecaptchaId = grecaptcha.render("g-recaptcha", {
    //             'sitekey' : '6LfgFhEUAAAAAG7lOhroItXiJiO53EOZt-ui0FfY',
    //             'callback' : function(response) {
    //                 //verify
    //                 var v_arg = { clklog_captcha : 1, response : response, key : self.UserKey };
    //                 $.get(self.urls.adShieldHandler, v_arg, function(d)
    //                 {
    //                     if (savedLogId > 0 && d.success)
    //                     {
    //                         //indicate that the captcha was successfully completed
    //                         $.get(self.urls.adShieldHandler, { key : self.UserKey, lgadclk_up : 1, id : savedLogId }, function() { });
    //                         self.LogCaptcha(savedLogId, 1);
    //                         captchaStatus = 'passed';
    //                         //hide captcha form
    //                         $('#recaptcha-holder').hide();
    //                         onCaptchaHide();
    //                         $("div#" + adShieldID).remove();
    //                         if (typeof onSuccess != 'undefined') onSuccess();
    //                     }
    //                     else
    //                     {
    //                         self.LogCaptcha(savedLogId, 0);
    //                         captchaStatus = 'failed';
    //                     }
    //                 }, 'json');
    //             }
    //         });
    //     }

    //     //add event handler for close button
    //     $("button#btn-close-captcha").click(function()
    //     {
    //         self.LogCaptcha(savedLogId, 3);
    //         $("#recaptcha-holder").hide();
    //         $(".tribeos-bg").hide();
    //         onCaptchaHide();
    //     });

    //     $(".tribeos-bg").css({
    //         height : window.innerHeight + "px"
    //     }).show();

    //     //show the recaptcha form
    //     $('#recaptcha-holder').show();

    //     //log captcha
    //     self.LogCaptcha(savedLogId, 2);
    // }

    /**
     * logs the click on the adshield. then performs an IP check (whitelisted, greylisted, blacklisted)
     * @param {[type]} onComplete [description]
     * @param {[type]} opt        [description]
     */
    self.LogAdShieldClick = function(onComplete, opt)
    {
        //get user agent
        var userAgent = navigator.userAgent;
        //get referrer url
        var refererUrl = document.referrer;
        //get target url
        var targetUrl = document.location.href;
        //get subsource
        var subsource = GetParameterByName("utm_medium");
        var arg = {
            key : self.UserKey,
            userAgent : encodeURIComponent(userAgent), refererUrl : encodeURIComponent(refererUrl),
            targetUrl : encodeURIComponent(targetUrl), subsource : subsource,
            lgadclk : 1
        };

        if (typeof opt != 'undefined')
        {
            if (typeof opt.ad_type != 'undefined') arg.ad_type = opt.ad_type;
            if (typeof opt.status != 'undefined') arg.status = opt.status;
        }

        //log this click
        $.post(self.urls.adShieldHandler, arg, function(d)
        {
            onComplete(d.id, d.status);
        }, 'json');
    }

    self.ActivateShieldForAds = function(adShieldID, selectors)
    {
        $(selectors).each(function(index)
        {
            var shield = $(document.createElement('div'));
            css = { position: "absolute", top: 0, left: 0, width: '100%', height: '100%',
                    background: "#000", opacity: "0.0", "z-index" : "1" }
            shield.css(css);
            shield.attr("id", adShieldID);
            var type = $(this).prop("tagName");
            if (type == "IFRAME")
            {
                $(this).wrap("<div></div>");
                var iframe = $(this);
                var anchor = iframe.closest("div");
                $(anchor).css({ position: 'relative' }).append(shield);
            } 
            else 
            {
                $(this).css({ position : 'relative' }).append(shield);
            }
        });
    }


    /**
     * handles the "Guess Estimate" of clicks on an Ad using mouseover and onblur events
     * [Display description]
     * @type {[type]}
     * Display: Google
        Sovrn, DistrictM isn’t present yet

    Native: Revcontent
        advertise.com, MGID, Zergnet will likely be killed soon.
     */
    self.ClickedMyAd = {
        overAnAd : false,
        clickIsHandled : false,
        statusValue : 0,
        adType : 0, //1=native, 0=display
        selectors : "ins.adsbygoogle ins ins",
        isMouseMoving : false,
        onClick : function() {},
        init : function(options) 
        {
            var self = this;
            if (typeof options.selectors != 'undefined') self.selectors = options.selectors;
            if (typeof options.onClick != 'undefined') self.onClick = options.onClick;

            $(self.selectors)
                .mouseover(function() {
                    if (self.overAnAd) return;
                    self.overAnAd = true;
                    self.adType = self.getType($(this));
                })
                .mouseout(function() {
                    if (self.isMouseMoving) self.overAnAd = false;
                });

            $(window).blur(function() {
                if (self.clickIsHandled) return;
                if (!self.overAnAd) return;
                //we assume user clicked on the ad
                self.onClick();
                self.overAnAd = false;
            }).focus();

            var timeout;
            $(document).mousemove(function() {
                self.isMouseMoving = true;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    self.isMouseMoving = false;
                }, 500);
            });
        },
        getType : function(dom) {
            //determine which element/dom is being hovered over
            var tag = dom.prop("tagName");
            var id = typeof dom.attr("id") == 'undefined' ? '' : dom.attr("id");
            var cls = typeof dom.attr("class") == 'undefined' ? '' : dom.attr("class");
            if (tag == "INS") 
            { //adsense
                return 0;
            } 
            else if (id.indexOf("rcjsload") > -1) 
            { //revcontent
                return 1;
            } 
            else if (id.indexOf("revexitunit") > -1) 
            { //revcontent popup
                return 1;
            } 
            else if (cls.indexOf("advertiseAd") > -1) 
            { //advertise.com
                return 1;
            }
            else if (cls.indexOf("redirectAd") > -1) 
            { //redirect
                return 1; //? confirm 
            } 
            else if (cls.indexOf("zergnetAd") > -1) 
            { //zergnet
                return 1; //? confirm 
            } 
            else if (tag == "IFRAME") 
            { //adcash (for now its the only iframed ad without ID we have)
                return 0; //display ad
            } 
            else 
            {    //for anything else that adshield is protecting
                return 1;
            }
            return -1;
        },

        setClickCatch : function(c)
        {
            this.clickIsHandled = c;
        },

        //for adding an el under adshield (for late adding, like on popups)
        addElementToProtect : function(selector)
        {
            var self = this;
            $(selector)
                .mouseover(function()
                {
                    if (self.overAnAd) return;
                    self.overAnAd = true;
                    self.adType = self.getType($(this));
                })
                .mouseout(function()
                {
                    self.overAnAd = false;
                });

            $(window).blur(function()
            {
                if (self.clickIsHandled) return;
                if (!self.overAnAd) return;
                //we assume user clicked on the ad
                self.onClick();
                self.overAnAd = false;
            }).focus();
            self.lateShielding(selector);
        },

        //we apply this to late added element to protect
        lateShielding : function (selector)
        {
            //empty by default
        }

    }

    self.LogCaptcha = function(l, s) 
    {
        $.post(self.urls.adShieldHandler, 
            { 
                key : self.UserKey, logCaptcha : 1, log_id : l, status : s
            }, 
            function(d) {}
        );
    }

    self.FillValue = function(key, value)
    {
        switch(key)
        {
            case 'key' : self.UserKey = value; break;
        }
    }

    self.Init = function()
    {
        for(var i in _adshield)
        {
            for(var k in _adshield[i])
            {
                self.FillValue(k, _adshield[i][k]);
            }
        }

        if (self.UserKey == null) 
        {
            throw {
                name : "Credential required",
                message : "Can't find your UserKey. Please follow the instruction on integration from our doc page."
            }
        }
        self.AdShieldType = 3; //not used yet
        self.CheckIframed();
        self.CheckReferrerUrl();
        self.StartAdShield();
    }

}
AdShield = new AdShield();