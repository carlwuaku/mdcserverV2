# Frontend Image Authentication Guide

This guide explains how to display authenticated images in your frontend application.

## Problem
Images referenced as URLs in `<img src="...">` tags don't automatically send authentication headers, causing 401/403 errors after securing the file server endpoints.

## Solutions

### Solution 1: Signed URLs (Recommended - Most Efficient)

Generate time-limited signed URLs that work in standard `<img>` tags without requiring auth headers.

#### Backend API (Already Implemented)
```
GET /file-server/sign-url/{type}/{filename}
Authorization: Bearer {token}

Response:
{
  "url": "http://localhost:8080/file-server/secure/applications/file.jpg?expires=1234567890&signature=abc123...",
  "expires_at": "2025-11-01 19:47:21"
}
```

#### Frontend Implementation

**React Example:**
```jsx
import { useState, useEffect } from 'react';
import axios from 'axios';

function PractitionerImage({ imageType, filename }) {
  const [signedUrl, setSignedUrl] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchSignedUrl() {
      try {
        const response = await axios.get(
          `/file-server/sign-url/${imageType}/${filename}`,
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('auth_token')}`
            }
          }
        );
        setSignedUrl(response.data.url);
      } catch (err) {
        setError('Failed to load image');
        console.error(err);
      } finally {
        setLoading(false);
      }
    }

    fetchSignedUrl();
  }, [imageType, filename]);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>{error}</div>;

  return <img src={signedUrl} alt="Practitioner" />;
}

// Usage
<PractitionerImage
  imageType="practitioners_images"
  filename="1762017079_f95807b21b7ffe5dc134.jpg"
/>
```

**Vue 3 Example:**
```vue
<template>
  <div>
    <img v-if="signedUrl" :src="signedUrl" alt="Practitioner" />
    <div v-else-if="loading">Loading...</div>
    <div v-else-if="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  imageType: String,
  filename: String
});

const signedUrl = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const response = await axios.get(
      `/file-server/sign-url/${props.imageType}/${props.filename}`,
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`
        }
      }
    );
    signedUrl.value = response.data.url;
  } catch (err) {
    error.value = 'Failed to load image';
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>
```

**Angular Example:**
```typescript
// image.component.ts
import { Component, Input, OnInit } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Component({
  selector: 'app-secure-image',
  template: `
    <img *ngIf="signedUrl" [src]="signedUrl" alt="Image" />
    <div *ngIf="loading">Loading...</div>
    <div *ngIf="error">{{ error }}</div>
  `
})
export class SecureImageComponent implements OnInit {
  @Input() imageType!: string;
  @Input() filename!: string;

  signedUrl: string | null = null;
  loading = true;
  error: string | null = null;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    const token = localStorage.getItem('auth_token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    this.http.get<any>(
      `/file-server/sign-url/${this.imageType}/${this.filename}`,
      { headers }
    ).subscribe({
      next: (response) => {
        this.signedUrl = response.url;
        this.loading = false;
      },
      error: (err) => {
        this.error = 'Failed to load image';
        this.loading = false;
        console.error(err);
      }
    });
  }
}
```

**Vanilla JavaScript:**
```javascript
async function loadSecureImage(imageType, filename, imgElement) {
  const token = localStorage.getItem('auth_token');

  try {
    const response = await fetch(
      `/file-server/sign-url/${imageType}/${filename}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );

    if (!response.ok) throw new Error('Failed to generate signed URL');

    const data = await response.json();
    imgElement.src = data.url;
  } catch (error) {
    console.error('Error loading image:', error);
    imgElement.alt = 'Failed to load image';
  }
}

// Usage
const img = document.getElementById('practitioner-photo');
loadSecureImage('practitioners_images', '1762017079_f95807b21b7ffe5dc134.jpg', img);
```

---

### Solution 2: Blob URLs with Fetch API

Fetch the image with auth headers and convert to a blob URL. More secure but uses more bandwidth.

**React Example:**
```jsx
import { useState, useEffect } from 'react';

function SecureImageBlob({ imageUrl }) {
  const [blobUrl, setBlobUrl] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let objectUrl = null;

    async function loadImage() {
      try {
        const response = await fetch(imageUrl, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`
          }
        });

        if (!response.ok) throw new Error('Failed to load image');

        const blob = await response.blob();
        objectUrl = URL.createObjectURL(blob);
        setBlobUrl(objectUrl);
      } catch (error) {
        console.error('Error loading image:', error);
      } finally {
        setLoading(false);
      }
    }

    loadImage();

    // Cleanup: revoke blob URL to prevent memory leaks
    return () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
      }
    };
  }, [imageUrl]);

  if (loading) return <div>Loading...</div>;
  if (!blobUrl) return <div>Failed to load image</div>;

  return <img src={blobUrl} alt="Secure content" />;
}

// Usage
<SecureImageBlob imageUrl="http://localhost:8080/file-server/image-render/applications/file.jpg" />
```

**Vue 3 Composable:**
```javascript
// useSecureImage.js
import { ref, onUnmounted, watchEffect } from 'vue';

export function useSecureImage(imageUrl) {
  const blobUrl = ref(null);
  const loading = ref(false);
  const error = ref(null);

  watchEffect(async () => {
    if (!imageUrl.value) return;

    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(imageUrl.value, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`
        }
      });

      if (!response.ok) throw new Error('Failed to load image');

      const blob = await response.blob();

      // Revoke previous blob URL if exists
      if (blobUrl.value) {
        URL.revokeObjectURL(blobUrl.value);
      }

      blobUrl.value = URL.createObjectURL(blob);
    } catch (err) {
      error.value = err.message;
    } finally {
      loading.value = false;
    }
  });

  // Cleanup on unmount
  onUnmounted(() => {
    if (blobUrl.value) {
      URL.revokeObjectURL(blobUrl.value);
    }
  });

  return { blobUrl, loading, error };
}

// Usage in component
<script setup>
import { ref } from 'vue';
import { useSecureImage } from './useSecureImage';

const imageUrl = ref('http://localhost:8080/file-server/image-render/applications/file.jpg');
const { blobUrl, loading, error } = useSecureImage(imageUrl);
</script>

<template>
  <img v-if="blobUrl" :src="blobUrl" alt="Secure image" />
  <div v-else-if="loading">Loading...</div>
  <div v-else-if="error">{{ error }}</div>
</template>
```

---

### Solution 3: Reusable Component with Caching

Create a reusable component that caches signed URLs to minimize API calls:

```jsx
// SecureImage.jsx (React)
import { useState, useEffect } from 'react';
import axios from 'axios';

// Simple in-memory cache
const urlCache = new Map();
const CACHE_DURATION = 50 * 60 * 1000; // 50 minutes (before 1 hour expiry)

export function SecureImage({ type, filename, alt, className, ...props }) {
  const [url, setUrl] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const cacheKey = `${type}/${filename}`;

    async function loadUrl() {
      // Check cache first
      const cached = urlCache.get(cacheKey);
      if (cached && Date.now() < cached.expiresAt) {
        setUrl(cached.url);
        setLoading(false);
        return;
      }

      try {
        const response = await axios.get(
          `/file-server/sign-url/${type}/${filename}`,
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('auth_token')}`
            }
          }
        );

        const signedUrl = response.data.url;
        const expiresAt = Date.now() + CACHE_DURATION;

        // Cache the URL
        urlCache.set(cacheKey, { url: signedUrl, expiresAt });

        setUrl(signedUrl);
      } catch (err) {
        setError('Failed to load image');
        console.error(err);
      } finally {
        setLoading(false);
      }
    }

    loadUrl();
  }, [type, filename]);

  if (loading) return <div className={className}>Loading...</div>;
  if (error) return <div className={className}>{error}</div>;

  return <img src={url} alt={alt} className={className} {...props} />;
}

// Usage
<SecureImage
  type="practitioners_images"
  filename="1762017079_f95807b21b7ffe5dc134.jpg"
  alt="Practitioner Photo"
  className="rounded-full w-32 h-32"
/>
```

---

## Migration Guide

### If you're currently using direct URLs:

**Before:**
```jsx
<img src="http://localhost:8080/file-server/image-render/applications/file.jpg" />
```

**After (Option 1 - Signed URLs):**
```jsx
<SecureImage type="applications" filename="file.jpg" />
```

**After (Option 2 - Blob URLs):**
```jsx
<SecureImageBlob imageUrl="http://localhost:8080/file-server/image-render/applications/file.jpg" />
```

### For API responses that include image URLs:

Update your backend to return relative paths instead of full URLs, then construct signed URLs in the frontend:

**Backend (Before):**
```php
return [
    'picture' => base_url("file-server/image-render/practitioners_images/photo.jpg")
];
```

**Backend (After):**
```php
return [
    'picture' => 'photo.jpg',
    'picture_type' => 'practitioners_images'
];
```

**Frontend:**
```jsx
// Then use the SecureImage component
<SecureImage type={data.picture_type} filename={data.picture} />
```

---

## Performance Considerations

1. **Signed URLs (Recommended)**:
   - ✅ Works with standard `<img>` tags
   - ✅ Browser caching works normally
   - ✅ Can be cached on frontend
   - ❌ Requires initial API call per unique image

2. **Blob URLs**:
   - ❌ Full image downloaded each time (no browser caching)
   - ❌ Higher memory usage
   - ✅ More secure (URL not shareable)
   - ❌ Doesn't work with lazy loading

3. **Best Practice**: Use signed URLs with frontend caching for optimal performance

---

## Security Notes

- Signed URLs expire after 1 hour (configurable in `AssetController::generateSignedUrl()`)
- URLs are validated server-side with HMAC signatures
- Even with a signed URL, files are validated against allowed types and directories
- Old blob URLs should be revoked to prevent memory leaks

## Troubleshooting

### Images not loading
1. Check browser console for 401/403 errors
2. Verify JWT token is present: `localStorage.getItem('auth_token')`
3. Check network tab to see the actual request being made

### CORS errors
Add the signed URL endpoint to your CORS whitelist in `app/Config/Filters.php` if needed.

### Performance issues with many images
Use the caching strategy shown in Solution 3 to minimize repeated API calls for the same images.
