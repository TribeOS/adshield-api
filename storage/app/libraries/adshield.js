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

    //create "closest()" function
    this.Element && function(ElementPrototype) {
        ElementPrototype.matches = ElementPrototype.matches ||
        ElementPrototype.matchesSelector ||
        ElementPrototype.webkitMatchesSelector ||
        ElementPrototype.msMatchesSelector ||
        function(selector) {
            var node = this, nodes = (node.parentNode || node.document).querySelectorAll(selector), i = -1;
            while (nodes[++i] && nodes[i] != node);
            return !!nodes[i];
        }
    }(Element.prototype);

    // closest polyfill
    this.Element && function(ElementPrototype) {
        ElementPrototype.closest = ElementPrototype.closest ||
        function(selector) {
            var el = this;
            while (el.matches && !el.matches(selector)) el = el.parentNode;
            return el.matches ? el : null;
        }
    }(Element.prototype);

    GetParameterByName = function(name, url)
    {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    self.httpGet = function(url, arg, callback)
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

    self.httpPost = function(url, arg, callback)
    {
        var xhttp = new XMLHttpRequest();
        xhttp.addEventListener("load", callback);
        xhttp.open("POST", url, true);
        xhttp.setRequestHeader("Content-Type", "application/json");
        xhttp.send(JSON.stringify(arg));
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
        self.httpPost(self.urls.statlog, arg, function(d) {});
    }

    /**
     * log and check if site is being run under an Iframe
     */
    self.CheckIframed = function()
    {
        if (window.top != window.self)
        {
            document.querySelectorAll('a').forEach(function(link) {
                link.onclick = function() {
                    window.open(link.href); return false;
                }
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
        self.httpPost(self.urls.checkReferrer, arg, function(d) {
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

        var adShieldID = "as" + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        if (shield_type == "1") {
            //cover all ads with blocker
            var ads = adshield_ads;
            self.ActivateShieldForAds(adShieldID, ads);
        } else if (shield_type == "2") {
            var shield = document.createElement('div');
            shield.style.position = "fixed";
            shield.style.top = 0;
            shield.style.left = 0;
            shield.style.right = 0;
            shield.style.bottom = 0;
            shield.style.background = "#000";
            shield.style.opacity = "0.0";
            shield.id = adShieldID;
            document.querySelector("body").appendChild(shield);
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
        self.httpPost(self.urls.adShieldHandler, { checkip : 1, key : self.UserKey }, function(d)
        {
            switch(d.status)
            {
                case "1":
                    self.ClickedMyAd.statusValue = 1;
                    //whitelisted
                    document.querySelectorAll("div#" + adShieldID).forEach(function(item) {
                        item.parentNode.removeChild(item);
                    });
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
        document.querySelectorAll("body div#" + adShieldID).forEach(function(elem) {
            elem.removeEventListener("click");
        })
        document.querySelectorAll("div#" + adShieldID).forEach(function(elem) {
            elem.removeEventListener("click");
        });
        document.querySelectorAll("div#" + adShieldID).forEach(function(elem) {
            elem.click(function() {
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
        self.httpPost(self.urls.adShieldHandler, arg, function(d)
        {
            onComplete(d.id, d.status);
        }, 'json');
    }

    self.ActivateShieldForAds = function(adShieldID, selectors)
    {
        document.querySelectorAll(selectors).forEach(function(item)
        {
            var shield = document.createElement('div');
            shield.style.position = "absolute";
            shield.style.top = 0;
            shield.style.left = 0;
            shield.style.width = "100%";
            shield.style.height = "100%";
            shield.style.background = "#000";
            shield.style.opacity = "0.0";
            shield.style.zIndex = "1";
            shield.id = adShieldID;
            var type = item.tagName;
            if (type == "IFRAME")
            {
                var wrp = document.createElement("div");
                item.parentNode.insertBefore(wrp, item);
                wrp.appendChild(item);
                var iframe = item;
                var anchor = iframe.closest("div");
                anchor.style.position = 'relative';
                anchor.appendChild(shield);
            } 
            else 
            {
                item.style.position = 'relative';
                item.appendChild(shield);
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

            document.querySelectorAll(self.selectors).forEach(function(elem) {
                elem.onmouseover = function() {
                    if (self.overAnAd) return;
                    self.overAnAd = true;
                    self.adType = self.getType(elem);
                }

                elem.onmouseout = function() {
                    if (self.isMouseMoving) self.overAnAd = false;
                }
            });

            window.onblur = function() {
                if (self.clickIsHandled) return;
                if (!self.overAnAd) return;
                //we assume user clicked on the ad
                self.onClick();
                self.overAnAd = false;
            }
            window.focus();

            var timeout;
            document.onmousemove = function() {
                self.isMouseMoving = true;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    self.isMouseMoving = false;
                }, 500);
            }
        },
        getType : function(dom) {
            //determine which element/dom is being hovered over
            var tag = dom.tagName;
            var id = typeof dom.id == 'undefined' ? '' : dom.id;
            var cls = typeof dom.className == 'undefined' ? '' : dom.className;
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
            document.querySelectorAll(selector).forEach(function(item) {
                    item.onmouseover = function()
                    {
                        if (self.overAnAd) return;
                        self.overAnAd = true;
                        self.adType = self.getType(item);
                    }
                    item.onmouseout = function()
                    {
                        self.overAnAd = false;
                    }
            });

            window.onblur(function()
            {
                if (self.clickIsHandled) return;
                if (!self.overAnAd) return;
                //we assume user clicked on the ad
                self.onClick();
                self.overAnAd = false;
            });
            window.focus();
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
        self.httpPost(self.urls.adShieldHandler, 
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

    self.CheckViolations = function()
    {
        var refererUrl = document.referrer;
        var arg = {
            refererUrl : refererUrl,
            fullUrl : encodeURIComponent(refererUrl),
            source : GetParameterByName("utm_source"),
            subsource : GetParameterByName("utm_medium"),
            visitUrl : "",
            userAgent : navigator.userAgent,
            jsCheck : false,
        }
        if (refererUrl.indexOf("://") > -1) { domain = refererUrl.split('/')[2]; }
        else { domain = refererUrl.split('/')[0]; }
        domain = domain.split(':')[0];
        arg.visitUrl = document.location.toString() || "";
        try {
            arg.jsCheck = self.checkJSEngine(); //perform our own check if user is using regular/normal JS objects in its js engine
        } catch (e) {}
        self.httpPost(self.urls.vlog + "/" + self.UserKey, arg, function(response) {
            //perform action here
            self.ViolationResponse(response.action);
        });   
    }

    /**
     * perform action as indicated on the parameter
     * @param {[type]} action [description]
     */
    self.ViolationResponse = function(action, options)
    {
        //act here :
        //- content protection
        //  - captcha
        //  - block page
        //- Custom page
        //  - captcha
        //  - block page
        //  - custom validation and itentity page
        // - content distribution
        //  - enable content cache
        //  - cache urls without extension
        //  - mobile cache
        //  - cache extension
        //  - compression & reroute
        //
        //
    }

    /**
     * check if we are in a browser or not
     * @return {[type]} [description]
     */
    self.checkJSEngine = function() {
        if (typeof document == "undefined") return false;
        if (typeof document.URL == "undefined") return false;
        if (typeof window == "undefined") return false;
        if (typeof window.alert == "undefined") return false;
        if (typeof document.body == "undefined") return false;
        return true;
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
        self.CheckViolations();
    }

}
AdShield = new AdShield();