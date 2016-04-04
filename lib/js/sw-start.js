(function (self, wpSwManager) {
  'use strict';

  self.addEventListener('fetch', wpSwManager.onFetch.bind(wpSwManager));
  wpSwManager.checkForUnregistering();
})(self, wpSwManager);
