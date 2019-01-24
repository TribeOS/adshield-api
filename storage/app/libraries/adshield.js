/**
 * Main JS for TribeOS' AdShield
 * No third party JS lib dependency
 *
 * expecting a global var _adshield array. with JSON element {key:'website key'}
 * UserKey : a unique user key for each website they register to TribeOS?
 */
AdShield = function()
{

    var self = this;
    self.urls = {};
    self.UserKey = null;
    self.AdShieldType = 3;
    self.violationId = 0;

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
        xhttp.open("POST", url, true);
        xhttp.addEventListener("load", function() {
            try {
                var data = JSON.parse(this.responseText);
                callback(data);
            } catch (e) {
                callback({});
            }
        });

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
            //places invisible divs on top of known ads and tracks clicks on it.
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


    /**
     * show captacha on the frontend
     * logs result to backend
     */
    self.RenderCaptcha = function() {
        //block whole page
        //show captcha
        //disallow other clicks
        //allow cancellation of captcha (no further action taken)
        //on submit, verify if success/failed from backend, wait for result
        //on success remove blocks and adshield?
    }


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

    // self.LogCaptcha = function(l, s) 
    // {
    //     self.httpPost(self.urls.adShieldHandler, 
    //         { 
    //             key : self.UserKey, logCaptcha : 1, log_id : l, status : s
    //         }, 
    //         function(d) {}
    //     );
    // }

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
            badBot : false,
            isAuto : false,
        }
        if (refererUrl.indexOf("://") > -1) { domain = refererUrl.split('/')[2]; }
        else { domain = refererUrl.split('/')[0]; }
        domain = domain.split(':')[0];
        arg.visitUrl = document.location.toString() || "";
        try {
            arg.jsCheck = self.checkJSEngine(); //perform our own check if user is using regular/normal JS objects in its js engine
        } catch (e) {}

        try {
            arg.badBot = self.isBadBot();
        } catch (e) {}
        try {
            arg.isAuto = self.isAuto();
        } catch (e) {}

        self.httpPost(self.urls.vlog + "/" + self.UserKey, arg, function(response) {
            //perform action here
            self.ViolationResponse(response);
        });   
    }

    /**
     * perform action as indicated on the parameter
     * @param {[type]} action [description]
     */
    self.ViolationResponse = function(response, options)
    {
        if (response.action == 'allow')
        {
            self.displayAds(response.jsCode);
        }
        else if (response.action == 'block')
        {
            //dont load anything
            return false;
        }
        else if (response.action == 'captcha')
        {
            self.violationId = response.violationId;
            self.displayCaptcha(response.jsCode);
        }
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

    self.isBadBot = function() {
        // let exr = /^$|\<|\>|\'|\%|\_iRc|\_Works|\@\$x|\<\?|\$x0e|\+select\+|\+union\+|1\,\1\,1\,|2icommerce|3GSE|4all|59\.64\.153\.|88\.0\.106\.|98|85\.17\.|A\_Browser|ABAC|Abont|abot|Accept|Access|Accoo|AceFTP|Acme|ActiveTouristBot|Address|Adopt|adress|adressendeutschland|ADSARobot|agent|ah\-ha|Ahead|AESOP\_com\_SpiderMan|aipbot|Alarm|Albert|Alek|Alexibot|Alligator|AllSubmitter|alma|almaden|ALot|Alpha|aktuelles|Akregat|Amfi|amzn\_assoc|Anal|Anarchie|andit|Anon|AnotherBot|Ansearch|AnswerBus|antivirx|Apexoo|appie|Aqua_Products|Arachmo|archive|arian|ASPSe|ASSORT|aster|Atari|ATHENS|AtHome|Atlocal|Atomic_Email_Hunter|Atomz|Atrop|^attach|attrib|autoemailspider|autohttp|axod|batch|b2w|Back|BackDoorBot|BackStreet|BackWeb|Badass|Baid|Bali|Bandit|Baidu|Barry|BasicHTTP|BatchFTP|bdfetch|beat|Become|Beij|BenchMark|berts|bew|big.brother|Bigfoot|Bilgi|Bison|Bitacle|Biz360|Black|Black.Hole|BlackWidow|bladder.fusion|Blaiz|Blog.Checker|Blogl|BlogPeople|Blogshares.Spiders|Bloodhound|Blow|bmclient|Board|BOI|boitho|Bond|Bookmark.search.tool|boris|Bost|Boston.Project|BotRightHere|Bot.mailto:craftbot@yahoo.com|BotALot|botpaidtoclick|botw|brandwatch|BravoBrian|Brok|Bropwers|Broth|browseabit|BrowseX|Browsezilla|Bruin|bsalsa|Buddy|Build|Built|Bulls|bumblebee|Bunny|Busca|Busi|Buy|bwh3|c\-spider|CafeK|Cafi|camel|Cand|captu|Catch|cd34|Ceg|CFNetwork|cgichk|Cha0s|Chang|chaos|Char|char\(32\,35\)|charlotte|CheeseBot|Chek|CherryPicker|chill|ChinaClaw|CICC|Cisco|Cita|Clam|Claw|Click.Bot|clipping|clshttp|Clush|COAST|ColdFusion|Coll|Comb|commentreader|Compan|contact|Control|contype|Conc|Conv|Copernic|Copi|Copy|Coral|Corn|core-project|cosmos|costa|cr4nk|crank|craft|Crap|Crawler0|Crazy|Cres|cs\-CZ|cuill|Curl|Custo|Cute|CSHttp|Cyber|cyberalert|^DA$|daoBot|DARK|Data|Daten|Daum|dcbot|dcs|Deep|DepS|Detect|Deweb|Diam|Digger|Digimarc|digout4uagent|DIIbot|Dillo|Ding|DISC|discobot|Disp|Ditto|DLC|DnloadMage|DotBot|Doubanbot|Download|Download.Demon|Download.Devil|Download.Wonder|Downloader|drag|DreamPassport|Drec|Drip|dsdl|dsok|DSurf|DTAAgent|DTS|Dual|dumb|DynaWeb|e\-collector|eag|earn|EARTHCOM|EasyDL|ebin|EBM-APPLE|EBrowse|eCatch|echo|ecollector|Edco|edgeio|efp\@gmx\.net|EirGrabber|email|Email.Extractor|EmailCollector|EmailSearch|EmailSiphon|EmailWolf|Emer|empas|Enfi|Enhan|Enterprise\_Search|envolk|erck|EroCr|ESurf|Eval|Evil|Evere|EWH|Exabot|Exact|EXPLOITER|Expre|Extra|ExtractorPro|EyeN|FairAd|Fake|FANG|FAST|fastlwspider|FavOrg|Favorites.Sweeper|Faxo|FDM\_1|FDSE|fetch|FEZhead|Filan|FileHound|find|Firebat|Firefox.2\.0|Firs|Flam|Flash|FlickBot|Flip|fluffy|flunky|focus|Foob|Fooky|Forex|Forum|ForV|Fost|Foto|Foun|Franklin.Locator|freefind|FreshDownload|FrontPage|FSurf|Fuck|Fuer|futile|Fyber|Gais|GalaxyBot|Galbot|Gamespy\_Arcade|GbPl|Gener|geni|Geona|Get|gigabaz|Gira|Ginxbot|gluc|glx.?v|gnome|Go.Zilla|Goldfire|Google.Wireless.Transcoder|Googlebot\-Image|Got\-It|GOFORIT|gonzo|GornKer|GoSearch|^gotit$|gozilla|grab|Grabber|GrabNet|Grub|Grup|Graf|Green.Research|grub|grub\-client|gsa\-cra|GSearch|GT\:\:WWW|GuideBot|guruji|gvfs|Gyps|hack|haha|hailo|Harv|Hatena|Hax|Head|Helm|herit|hgre|hhjhj\@yahoo|Hippo|hloader|HMView|holm|holy|HomePageSearch|HooWWWer|HouxouCrawler|HMSE|HPPrint|htdig|HTTPConnect|httpdown|http.generic|HTTPGet|httplib|HTTPRetriever|HTTrack|human|Huron|hverify|Hybrid|Hyper|ia\_archiver|iaskspi|IBM\_Planetwide|iCCra|ichiro|ID\-Search|IDA|IDBot|IEAuto|IEMPT|iexplore\.exe|iGetter|Ilse|Iltrov|Image|Image.Stripper|Image.Sucker|imagefetch|iimds\_monitor|Incutio|IncyWincy|Indexer|Industry.Program|Indy|InetURL|informant|InfoNav|InfoTekies|Ingelin|Innerpr|Inspect|InstallShield.DigitalWizard|Insuran\.|Intellig|Intelliseek|InterGET|Internet.Ninja|Internet.x|Internet\_Explorer|InternetLinkagent|InternetSeer.com|Intraf|IP2|Ipsel|Iria|IRLbot|Iron33|Irvine|ISC\_Sys|iSilo|ISRCCrawler|ISSpi|IUPUI.Research.Bot|Jady|Jaka|Jam|^Java|java\/|Java\(tm\)|JBH.agent|Jenny|JetB|JetC|jeteye|jiro|JoBo|JOC|jupit|Just|Jyx|Kapere|kash|Kazo|KBee|Kenjin|Kernel|Keywo|KFSW|KKma|Know|kosmix|KRAE|KRetrieve|Krug|ksibot|ksoap|Kum|KWebGet|Lachesis|lanshan|Lapo|larbin|leacher|leech|LeechFTP|LeechGet|leipzig\.de|Lets|Lexi|lftp|Libby|libcrawl|libcurl|libfetch|libghttp|libWeb|libwhisker|libwww|libwww\-FM|libwww\-perl|LightningDownload|likse|Linc|Link|Link.Sleuth|LinkextractorPro|Linkie|LINKS.ARoMATIZED|LinkScan|linktiger|LinkWalker|Lint|List|lmcrawler|LMQ|LNSpiderguy|loader|LocalcomBot|Locu|London|lone|looksmart|loop|Lork|LTH\_|lwp\-request|LWP|lwp-request|lwp-trivial|Mac.Finder|Macintosh\;.I\;.PPC|Mac\_F|magi|Mag\-Net|Magnet|Magp|Mail.Sweeper|main|majest|Mam|Mana|MarcoPolo|mark.blonin|MarkWatch|MaSagool|Mass|Mass.Downloader|Mata|mavi|McBot|Mecha|MCspider|mediapartners|^Memo|MEGAUPLOAD|MetaProducts.Download.Express|Metaspin|Mete|Microsoft.Data.Access|Microsoft.URL|Microsoft\_Internet\_Explorer|MIDo|MIIx|miner|Mira|MIRE|Mirror|Miss|Missauga|Missigua.Locator|Missouri.College.Browse|Mist|Mizz|MJ12|mkdb|mlbot|MLM|MMMoCrawl|MnoG|moge|Moje|Monster|Monza.Browser|Mooz|Moreoverbot|MOT\-MPx220|mothra\/netscan|mouse|MovableType|Mozdex|Mozi\!|^Mozilla$|Mozilla\/1\.22|Mozilla\/22|^Mozilla\/3\.0.\(compatible|Mozilla\/3\.Mozilla\/2\.01|Mozilla\/4\.0\(compatible|Mozilla\/4\.08|Mozilla\/4\.61.\(Macintosh|Mozilla\/5\.0|Mozilla\/7\.0|Mozilla\/8|Mozilla\/9|Mozilla\:|Mozilla\/Firefox|^Mozilla.*Indy|^Mozilla.*NEWT|^Mozilla*MSIECrawler|Mp3Bot|MPF|MRA|MS.FrontPage|MS.?Search|MSFrontPage|MSIE\_6\.0|MSIE6|MSIECrawler|msnbot\-media|msnbot\-Products|MSNPTC|MSProxy|MSRBOT|multithreaddb|musc|MVAC|MWM|My\_age|MyApp|MyDog|MyEng|MyFamilyBot|MyGetRight|MyIE2|mysearch|myurl|NAG|NAMEPROTECT|NASA.Search|nationaldirectory|Naver|Navr|Near|NetAnts|netattache|Netcach|NetCarta|Netcraft|NetCrawl|NetMech|netprospector|NetResearchServer|NetSp|Net.Vampire|netX|NetZ|Neut|newLISP|NewsGatorInbox|NEWT|NEWT.ActiveX|Next|^NG|NICE|nikto|Nimb|Ninja|Ninte|NIPGCrawler|Noga|nogo|Noko|Nomad|Norb|noxtrumbot|NPbot|NuSe|Nutch|Nutex|NWSp|Obje|Ocel|Octo|ODI3|oegp|Offline|Offline.Explorer|Offline.Navigator|OK.Mozilla|omg|Omni|Onfo|onyx|OpaL|OpenBot|Openf|OpenTextSiteCrawler|OpenU|Orac|OrangeBot|Orbit|Oreg|osis|Outf|Owl|P3P|PackRat|PageGrabber|PagmIEDownload|pansci|Papa|Pars|Patw|pavu|Pb2Pb|pcBrow|PEAR|PEER|PECL|pepe|Perl|PerMan|PersonaPilot|Persuader|petit|PHP|PHP.vers|PHPot|Phras|PicaLo|Piff|Pige|pigs|^Ping|Pingd|PingALink|Pipe|Plag|Plant|playstarmusic|Pluck|Pockey|POE\-Com|Poirot|Pomp|Port.Huron|Post|powerset|Preload|press|Privoxy|Probe|Program.Shareware|Progressive.Download|ProPowerBot|prospector|Provider.Protocol.Discover|ProWebWalker|Prowl|Proxy|Prozilla|psbot|PSurf|psycheclone|^puf$|Pulse|Pump|PushSite|PussyCat|PuxaRapido|PycURL|Pyth|PyQ|QuepasaCreep|Query|Quest|QRVA|Qweer|radian|Radiation|Rambler|RAMP|RealDownload|Reap|Recorder|RedCarpet|RedKernel|ReGet|relevantnoise|replacer|Repo|requ|Rese|Retrieve|Rip|Rix|RMA|Roboz|Rogue|Rover|RPT\-HTTP|Rsync|RTG30|.ru\)|ruby|Rufus|Salt|Sample|SAPO|Sauger|savvy|SBIder|SBP|SCAgent|scan|SCEJ\_|Sched|Schizo|Schlong|Schmo|Scout|Scooter|Scorp|ScoutOut|SCrawl|screen|script|SearchExpress|searchhippo|Searchme|searchpreview|searchterms|Second.Street.Research|Security.Kol|Seekbot|Seeker|Sega|Sensis|Sept|Serious|Sezn|Shai|Share|Sharp|Shaz|shell|shelo|Sherl|Shim|Shiretoko|ShopWiki|SickleBot|Simple|Siph|sitecheck|SiteCrawler|SiteSnagger|Site.Sniper|SiteSucker|sitevigil|SiteX|Sleip|Slide|Slurpy.Verifier|Sly|Smag|SmartDownload|Smurf|sna\-|snag|Snake|Snapbot|Snip|Snoop|So\-net|SocSci|sogou|Sohu|solr|sootle|Soso|SpaceBison|Spad|Span|spanner|Speed|Spegla|Sphere|Sphider|spider|SpiderBot|SpiderEngine|SpiderView|Spin|sproose|Spurl|Spyder|Squi|SQ.Webscanner|sqwid|Sqworm|SSM\_Ag|Stack|Stamina|stamp|Stanford|Statbot|State|Steel|Strateg|Stress|Strip|studybot|Style|subot|Suck|Sume|sun4m|Sunrise|SuperBot|SuperBro|Supervi|Surf4Me|SuperHTTP|Surfbot|SurfWalker|Susi|suza|suzu|Sweep|sygol|syncrisis|Systems|Szukacz|Tagger|Tagyu|tAke|Talkro|TALWinHttpClient|tamu|Tandem|Tarantula|tarspider|tBot|TCF|Tcs\/1|TeamSoft|Tecomi|Teleport|Telesoft|Templeton|Tencent|Terrawiz|Test|TexNut|trivial|Turnitin|The.Intraformant|TheNomad|Thomas|TightTwatBot|Timely|Titan|TMCrawler|TMhtload|toCrawl|Todobr|Tongco|topic|Torrent|Track|translate|Traveler|TREEVIEW|True|Tunnel|turing|Turnitin|TutorGig|TV33\_Mercator|Twat|Tweak|Twice|Twisted.PageGetter|Tygo|ubee|UCmore|UdmSearch|UIowaCrawler|Ultraseek|UMBC|unf|UniversalFeedParser|unknown|UPG1|UtilMind|URLBase|URL.Control|URL\_Spider\_Pro|urldispatcher|URLGetFile|urllib|URLSpiderPro|URLy|User\-Agent|UserAgent|USyd|Vacuum|vagabo|Valet|Valid|Vamp|vayala|VB\_|VCI|VERI\~LI|verif|versus|via|Viewer|virtual|visibilitygap|Visual|vobsub|Void|VoilaBot|voyager|vspider|VSyn|w\:PACBHO60|w0000t|W3C|w3m|w3search|walhello|Walker|Wand|WAOL|WAPT|Watch|Wavefire|wbdbot|Weather|web.by.mail|Web.Data.Extractor|Web.Downloader|Web.Ima|Web.Mole|Web.Sucker|Web2Mal|Web2WAP|WebaltBot|WebAuto|WebBandit|Webbot|WebCapture|WebCat|webcraft\@bea|Webclip|webcollage|WebCollector|WebCopier|WebCopy|WebCor|webcrawl|WebDat|WebDav|webdevil|webdownloader|Webdup|WebEMail|WebEMailExtrac|WebEnhancer|WebFetch|WebGo|WebHook|Webinator|WebInd|webitpr|WebFilter|WebFountain|WebLea|Webmaster|WebmasterWorldForumBot|WebMin|WebMirror|webmole|webpic|WebPin|WebPix|WebReaper|WebRipper|WebRobot|WebSauger|WebSite|Website.eXtractor|Website.Quester|WebSnake|webspider|Webster|WebStripper|websucker|WebTre|WebVac|webwalk|WebWasher|WebWeasel|WebWhacker|WebZIP|Wells|WEP\_S|WEP.Search.00|WeRelateBot|wget|Whack|Whacker|whiz|WhosTalking|Widow|Win67|window.location|Windows.95\;|Windows.95\)|Windows.98\;|Windows.98\)|Winodws|Wildsoft.Surfer|WinHT|winhttp|WinHttpRequest|WinHTTrack|Winnie.Poh|wire|WISEbot|wisenutbot|wish|Wizz|WordP|Works|world|WUMPUS|Wweb|WWWC|WWWOFFLE|WWW\-Collector|WWW.Mechanize|www.ranks.nl|wwwster|^x$|X12R1|x\-Tractor|Xaldon|Xenu|XGET|xirq|Y\!OASIS|Y\!Tunnel|yacy|YaDirectBot|Yahoo\-MMAudVid|YahooSeeker|YahooYSMcm|Yamm|Yand|yang|Yeti|Yoono|yori|Yotta|YTunnel|Zade|zagre|ZBot|Zeal|ZeBot|zerx|Zeus|ZIPCode|Zixy|zmao|Zyborg/;
        // let ua = navigator.userAgent;
        
        // return ua.match(exr) !== null;
        return false;
    }

    /**
    *   Display captcha to user
    **/
    self.displayCaptcha = function(jsCode) {
        self.constructCaptcha(function() {
            //success
            self.displayAds(jsCode);
        }, function() {
            //cancelled
            console.log("not showing ads");
        });
    }

    /**
     * execute code to display ads to their respective tags/holders
     */
    self.displayAds = function(jsCode) {
        //IMPT:: ad code would be enabled here vvvvv
        //add js code that loads the actual ads here
        //-----------
        var tmpCode = jsCode.split("<scrip");
        //re construct to more acceptable string for document.write();
        if (tmpCode.length > 0) {
            for(var i in tmpCode) {
                if (i > 0) {
                    document.write("<scrip");
                    document.write(tmpCode[i]);
                }
            }
        } else {
            document.write(jsCode);
        }
    }


    /**
     * constructs the DOM element for the captcha service
     * @return {[type]} [description]
     */
    self.constructCaptcha = function(onSuccess, onCancel) {

        var bd = document.createElement("div");
        bd.style.top = 0;
        bd.style.left = 0;
        bd.style.backgroundColor = "#000";
        bd.style.opacity = 0.6;
        bd.style.width = "100%";
        bd.style.height = "100%";
        bd.style.position = "fixed";
        bd.style.zIndex = "9999999999999999999999999";
        document.body.appendChild(bd);

        var f = document.createElement("div");
        f.style.width = "300px";
        f.style.height = "200px";
        f.style.backgroundColor = "#eee";
        f.style.left = "50%";
        f.style.top = "50%";
        f.style.position = "absolute";
        f.style.transform = "translate(-50%, -50%)";
        f.style.zIndex = bd.style.zIndex + "1";
        f.style.padding = "20px";
        document.body.appendChild(f);

        //captcha holder
        var ch = document.createElement("div");
        f.appendChild(ch);

        self.generateCaptcha(ch);

        var i = document.createElement("input");
        i.type = "text";
        i.style.width = "100%";
        i.style.padding = "6px";
        f.appendChild(i);

        //submit button
        var button = document.createElement("button");
        button.textContent = "Submit";
        button.onclick = function() {
            var result = self.validateCaptcha(i.value);
            //inform backend of result
            if (result) {
                self.httpPost(self.urls.captchaUrl + "/" + self.UserKey + "/success", { violationId : self.violationId }, function(d) {
                    onSuccess();
                    document.body.removeChild(bd);
                    document.body.removeChild(f);
                });
                //success
            } else {
                self.httpPost(self.urls.captchaUrl + "/" + self.UserKey + "/failed", { violationId : self.violationId }, function(d) {
                    self.generateCaptcha(ch);
                });
                //failed
            }
        }
        f.appendChild(button);

        //refreh button
        var buttonRefresh = document.createElement("button");
        buttonRefresh.textContent = "Refresh";
        buttonRefresh.onclick = function() {
            i.value = "";
            self.generateCaptcha(ch);
        }
        f.appendChild(buttonRefresh);

        //cancel button
        var buttonCancel = document.createElement("button");
        buttonCancel.textContent = "Cancel";
        buttonCancel.onclick = function() {
            self.httpPost(self.urls.captchaUrl + "/" + self.UserKey + "/cancelled", { violationId : self.violationId }, function(d) {
                document.body.removeChild(bd);
                document.body.removeChild(f);
                onCancel();
            });
        }
        f.appendChild(buttonCancel);

        self.httpPost(self.urls.captchaUrl + "/" + self.UserKey + "/shown", { violationId : self.violationId }, function(d) { });
    }

    /**
     * generates a new captcha code
     * @return {[type]} [description]
     */
    self.generateCaptcha = function(captchaHolder) {
        //clear the contents of captcha div first 
        captchaHolder.innerHTML = "";
        var charsArray =
            "0123456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        var lengthOtp = 6;
        var captcha = [];
        for (var i = 0; i < lengthOtp; i++) {
            //below code will not allow Repetition of Characters
            var index = Math.floor(Math.random() * charsArray.length + 1); //get the next character from the array
            if (captcha.indexOf(charsArray[index]) == -1)
                captcha.push(charsArray[index]);
            else i--;
        }
        var canv = document.createElement("canvas");
        canv.id = "captcha";
        canv.width = 100;
        canv.height = 50;
        var ctx = canv.getContext("2d");
        ctx.font = "25px Georgia";
        ctx.strokeText(captcha.join(""), 0, 30);
        //storing captcha so that can validate you can save it somewhere else according to your specific requirements
        self.code = captcha.join("");
        captchaHolder.appendChild(canv); // adds the canvas to the body element
    }

    /**
     * captcha validator
     * @return {[type]} [description]
     */
    self.validateCaptcha = function(answer) {
        event.preventDefault();
        return answer == self.code;
    }

    /**
     * try to check common signatures for automation tools
     * @return {Boolean} [description]
     */
    self.isAuto = function() {
        if (window.callPhantom || window._phantom) return "PhantomJS";
        if (/PhantomJS/.test(window.navigator.userAgent)) return "PhantomJS";
        if (!Function.prototype.bind) return "PhantomJS";
        if (Function.prototype.bind.toString().replace(/bind/g, 'Error') != Error.toString()) return "PhantomJS";
        if (Function.prototype.toString.toString().replace(/toString/g, 'Error') != Error.toString()) return "PhantomJS";
        if (navigator.webdriver == true) return "Selenium";
        if (window.document.documentElement.getAttribute("webdriver")) return "Selenium";
        return false;
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
        self.CheckIframed(); //check if website is inside an iframe or not
        self.CheckReferrerUrl(); //check the referrer of the request (bad or good referrer url)
        // self.StartAdShield(); //(try to) place div overlays on known ads
        self.CheckViolations(); //main violation/threat checker

    }

}
AdShield = new AdShield();