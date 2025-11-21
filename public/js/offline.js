// Offline support: IndexedDB metadata cache & upload queue
(function(){
  const DB_NAME = 'imanage-offline';
  const DB_VERSION = 1;
  const META_STORE = 'imageMeta';
  const QUEUE_STORE = 'uploadQueue';

  function openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = function(e){
        const db = req.result;
        if (!db.objectStoreNames.contains(META_STORE)) {
          db.createObjectStore(META_STORE, { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains(QUEUE_STORE)) {
          db.createObjectStore(QUEUE_STORE, { keyPath: 'id', autoIncrement: true });
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function putMeta(records) {
    const db = await openDB();
    const tx = db.transaction(META_STORE, 'readwrite');
    const store = tx.objectStore(META_STORE);
    records.forEach(r => store.put(r));
    return tx.complete;
  }

  async function getAllMeta() {
    const db = await openDB();
    const tx = db.transaction(META_STORE, 'readonly');
    const store = tx.objectStore(META_STORE);
    return new Promise(res => {
      const req = store.getAll();
      req.onsuccess = () => res(req.result);
      req.onerror = () => res([]);
    });
  }

  async function queueFormData(formData) {
    // Extract minimal metadata for gallery refresh while offline
    const files = formData.getAll('image');
    const folder = formData.get('folder') || 'default';
    const db = await openDB();
    const tx = db.transaction(QUEUE_STORE, 'readwrite');
    const store = tx.objectStore(QUEUE_STORE);
    files.forEach(file => {
      store.add({
        fileName: file.name,
        fileType: file.type,
        fileBlob: file,
        folder: folder,
        queuedAt: Date.now()
      });
    });
    // Register sync if available
    if (navigator.serviceWorker && 'sync' in Registration.prototype) {
      navigator.serviceWorker.ready.then(reg => reg.sync.register('upload-queue').catch(()=>{}));
    }
  }

  async function processQueue() {
    if (!navigator.onLine) return; // don't attempt when still offline
    const db = await openDB();
    const tx = db.transaction(QUEUE_STORE, 'readonly');
    const store = tx.objectStore(QUEUE_STORE);
    const all = await new Promise(res => { const req = store.getAll(); req.onsuccess=()=>res(req.result); req.onerror=()=>res([]); });
    if (!all.length) return;

    for (const item of all) {
      const fd = new FormData();
      fd.append('image', item.fileBlob, item.fileName);
      fd.append('folder', item.folder);
      try {
        const resp = await fetch('./api.php?action=upload', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data && data.success) {
          // Remove from queue
          const delTx = db.transaction(QUEUE_STORE, 'readwrite');
          delTx.objectStore(QUEUE_STORE).delete(item.id);
        }
      } catch (err) {
        // Leave item in queue for later retry
        console.warn('Retry later for queued upload', item.fileName, err);
      }
    }
    // Refresh gallery after processing
    if (typeof loadImages === 'function') loadImages();
  }

  // Listen for online event for fallback if Background Sync unsupported
  window.addEventListener('online', () => processQueue());

  // Public API
  window.offlineUploadQueue = { queueFormData, processQueue };

  // Metadata caching hook: wrap displayGallery to store metadata and read when offline
  const originalDisplay = window.displayGallery;
  window.displayGallery = function(images) {
    // Store metadata for offline reuse
    putMeta(images.map(img => ({ id: img.id, title: img.title || img.original_name, folder: img.folder, thumbnail_url: img.thumbnail_url }))).catch(()=>{});
    originalDisplay(images);
  };

  // If offline on load, attempt to display cached metadata
  if (!navigator.onLine) {
    getAllMeta().then(cached => {
      if (cached && cached.length && typeof originalDisplay === 'function') {
        originalDisplay(cached.map(c => ({ id: c.id, title: c.title, folder: c.folder, thumbnail_url: c.thumbnail_url, original_name: c.title })));
      }
    });
  }
})();
