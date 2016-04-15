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
        .then(function (timestamp) {
          timestamp = timestamp || 0;
          var now = Date.now();
          if (now - timestamp > this.ONE_DAY) {
            return Promise.all([
              this.setLastCheck(now),
              fetch(self.registration.active.scriptURL)
            ])
            .then(function (results) {
              var response = results[1];
              return response.text()
              .then(function (contents) {
                // In WP, '0' means no action or no callback found. Just what we want.
                return Promise.resolve(contents === '0');
              });
            }.bind(this));
          }
          return Promise.resolve(false);
        }.bind(this));
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
      .then(function() { this._lastCheck = Promise.resolve(value); }.bind(this));
    },

    onFetch: function (event) {
      event.waitUntil(this.checkForUnregistering().then(function (shouldUnregister) {
        if (shouldUnregister) {
          this.storage.clear();
          return this.unregister();
        }
        return Promise.resolve();
      }.bind(this)));
    },

    unregister: function () {
      return self.registration.unregister();
    }
  };

})(self, localforage);
