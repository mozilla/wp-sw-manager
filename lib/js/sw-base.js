(function (self, localforage) {
  'use strict';

  self.wpSwManager = {
    ONE_DAY: 24 * 60 * 60 * 1000, // in ms

    storage: localforage.createInstance({ name: '__wpswmanager'} ),

    checkForUnregistering: function () {
      if (!self.registration.active) {
        return Promise.resolve(false);
      }
      else {
        return this.getLastCheck()
        .then(timestamp => {
          timestamp = timestamp || 0;
          var now = Date.now();
          if (now - timestamp > this.ONE_DAY) {
            return Promise.all([
              this.setLastCheck(now),
              fetch(self.registration.active.scriptURL, { method: 'HEAD' })
            ])
            .then(results => {
              var response = results[1];
              // If not served as JavaScript, it is the error page from WordPress
              return response.headers.get('Content-Type').indexOf('javascript') < 0;
            });
          }
          return Promise.resolve(false);
        });
      }
    },

    getLastCheck: function () {
      if (!this._lastCheck) {
        this._lastCheck = this.storage.getItem('lastCheck');
      }
      return this._lastCheck;
    },

    setLastCheck: function (value) {
      return this.storage.setItem('lastCheck', value)
      .then(() => this._lastCheck = Promise.resolve(value));
    },

    onFetch: function (event) {
      event.waitUntil(this.checkForUnregistering().then(shouldUnregister => {
        if (shouldUnregister) {
          this.storage.clear();
          return this.unregister();
        }
        return Promise.resolve();
      }));
    },

    unregister: function () {
      return self.registration.unregister();
    }
  };

})(self, localforage);
