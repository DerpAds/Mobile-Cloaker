function makePopunder(pUrl) {

    var _parent = (top != self && typeof (top["document"]["location"].toString()) === "string") ? top : self;

    var mypopunder = null;
    var pName = (Math["floor"]((Math["random"]() * 1000) + 1));
    var pWidth = window["innerWidth"];
    var pHeight = window["innerHeight"];
    var pPosX = window["screenX"];
    var pPosY = window["screenY"];
    var pWait = 3600;
    pWait = (pWait * 1000);
    var pCap = 50000;
    var todayPops = 0;
    var cookie = "_.mypopunder";
    var browser = function () {
        var n = navigator["userAgent"]["toLowerCase"]();
        var b = {
            webkit: /webkit/ ["test"](n),
            mozilla: (/mozilla/ ["test"](n)) && (!/(compatible|webkit)/ ["test"](n)),
            chrome: /chrome/ ["test"](n),
            msie: (/msie/ ["test"](n)) && (!/opera/ ["test"](n)),
            firefox: /firefox/ ["test"](n),
            safari: (/safari/ ["test"](n) && !(/chrome/ ["test"](n))),
            opera: /opera/ ["test"](n)
        };
        b["version"] = (b["safari"]) ? (n["match"](/.+(?:ri)[\\/: ]([\\d.]+)/) || [])[1] : (n["match"](/.+(?:ox|me|ra|ie)[\\/: ]([\\d.]+)/) || [])[1];
        return b;
    }();


    function doPopunder(pUrl, pName, pWidth, pHeight, pPosX, pPosY) {

        var sOptions = "toolbar=no,scrollbars=yes,location=yes,statusbar=yes,menubar=no,resizable=1,width=" + pWidth.toString() + ",height=" + pHeight.toString() + ",screenX=" + pPosX + ",screenY=" + pPosY;
            window["pop_clicked"] = 1;
            mypopunder = _parent["window"]["open"](pUrl, pName, sOptions);
            if (mypopunder) {
                var now = new Date();
                document["cookie"] = cookie + "=1;expires=" + new Date(now["setTime"](now["getTime"]() + pWait))["toGMTString"]() + ";path=/";
                now = new Date();
                document["cookie"] = cookie + "Cap=" + (todayPops + 1) + ";expires=" + new Date(now["setTime"](now["getTime"]() + (84600 * 1000)))["toGMTString"]() + ";path=/";
                pop2under();
            };
    };

    function pop2under() {
        try {
            mypopunder["blur"]();
            mypopunder["opener"]["window"]["focus"]();
            window["self"]["window"]["blur"]();
            window["focus"]();
            if (browser["firefox"]) {
                openCloseWindow();
            };
            if (browser["webkit"]) {
                openCloseTab();
            };
        } catch (e) {};
    };

    function openCloseWindow() {
        var ghost = window["open"]("about:blank");
        ghost["focus"]();
        ghost["close"]();
    };

    function openCloseTab() {
        var ghost = document["createElement"]("a");
        ghost["href"] = "about:blank";
        ghost["target"] = "PopHelper";
        document["getElementsByTagName"]("body")[0]["appendChild"](ghost);
        ghost["parentNode"]["removeChild"](ghost);
        var clk = document["createEvent"]("MouseEvents");
        clk["initMouseEvent"]("click", true, true, window, 0, 0, 0, 0, 0, true, false, false, true, 0, null);
        ghost["dispatchEvent"](clk);
        window["open"]("about:blank", "PopHelper")["close"]();
    };

    doPopunder(pUrl, pName, pWidth, pHeight, pPosX, pPosY);
};

