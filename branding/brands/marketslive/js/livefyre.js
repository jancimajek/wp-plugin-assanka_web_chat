// Used to enable livefyre widget on Marketslive. Widget code will be an FT customized version of livefyre script; e.g: http://www.ft-static.com/sp/prod/marketslive-comments/0.2.0/long/marketsLiveCommentIntegration.min.js
// marketsLiveCommentIntegration.min.js is to be filled in Dashboard -> Settings -> Webchats -> Livefyre custom script URL
// Source code from loaded scripts: http://git.svc.ft.com:8080/projects/STRAT_P/repos/livefyre-client-integration/browse/public/javascripts
// Livefyre docs: https://github.com/Livefyre/livefyre-docs/wiki/

if (FT && FT.$ && (marketsLiveCommentIntegration && typeof marketsLiveCommentIntegration === 'object' && cutsTheMustard === true)) {

    FT.$(document).ready(function () {
        "use strict";

        var isOnFtDomain = function () {
            return window.location.hostname.indexOf('ft.com', window.location.hostname.length - 'ft.com'.length) !== -1;
        };

        if (FT && FT.$('#ft-article-comments').length && isOnFtDomain()) {
            var commentsSettings = {
                elId: 'livefyre-app-ft-' + FT.page.metadata.articleUuid,
                title: document.title,
                url: FT.$('link[rel=canonical]').attr('href') || document.location.href,
                articleId: FT.page.metadata.articleUuid
            };

            var marketsLiveChatWidget = new marketsLiveCommentIntegration.Widget(commentsSettings);

            var adaptHeightOfWidget = function () {
                var intervalTime = 0;

                var readaptHeight = function () {
                    if (intervalTime > 5) {
                        clearInterval(adaptHeightInterval);
                        return;
                    }

                    var heightInPlus = FT.$('.comments-panel').height() - FT.$('#ft-article-comments').height();
                    var targetHeight = FT.$('.chat-panel').height();
                    marketsLiveChatWidget.adaptToHeight(targetHeight - heightInPlus);

                    intervalTime++;
                };

                readaptHeight();
                var adaptHeightInterval = setInterval(readaptHeight, 2000);
            };

            FT.$(window).on('resize', function () {
                setTimeout(adaptHeightOfWidget, 200);
            });
            marketsLiveChatWidget.on('renderComplete.widget', adaptHeightOfWidget);
            marketsLiveChatWidget.load();
        }
    });
}
