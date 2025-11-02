# Angular 15 - Authenticated Image Implementation Guide

Complete guide for implementing authenticated image loading in Angular 15 applications.

## Quick Start

### 1. Create the Image Service

Create a service to handle signed URL generation and caching:

```bash
ng generate service services/secure-image
```

**File: `src/app/services/secure-image.service.ts`**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError, shareReplay } from 'rxjs/operators';

interface SignedUrlResponse {
  url: string;
  expires_at: string;
}

interface CachedUrl {
  url: string;
  expiresAt: number;
}

@Injectable({
  providedIn: 'root'
})
export class SecureImageService {
  private baseUrl = 'http://localhost:8080/file-server'; // Update with your API URL
  private cache = new Map<string, CachedUrl>();
  private readonly CACHE_BUFFER = 5 * 60 * 1000; // 5 minutes before actual expiry

  constructor(private http: HttpClient) {}

  /**
   * Get a signed URL for secure image access
   */
  getSignedUrl(imageType: string, filename: string): Observable<string> {
    const cacheKey = `${imageType}/${filename}`;

    // Check cache first
    const cached = this.cache.get(cacheKey);
    if (cached && Date.now() < cached.expiresAt - this.CACHE_BUFFER) {
      return of(cached.url);
    }

    // Fetch new signed URL
    const token = localStorage.getItem('auth_token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<SignedUrlResponse>(
      `${this.baseUrl}/sign-url/${imageType}/${filename}`,
      { headers }
    ).pipe(
      map(response => {
        // Cache the URL
        const expiresAt = new Date(response.expires_at).getTime();
        this.cache.set(cacheKey, { url: response.url, expiresAt });
        return response.url;
      }),
      catchError(error => {
        console.error('Error fetching signed URL:', error);
        return of(''); // Return empty string on error
      }),
      shareReplay(1) // Share the result among multiple subscribers
    );
  }

  /**
   * Get image as blob URL (alternative method - uses more bandwidth)
   */
  getImageAsBlob(imageUrl: string): Observable<string> {
    const token = localStorage.getItem('auth_token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get(imageUrl, {
      headers,
      responseType: 'blob'
    }).pipe(
      map(blob => URL.createObjectURL(blob)),
      catchError(error => {
        console.error('Error fetching image blob:', error);
        return of('');
      })
    );
  }

  /**
   * Clear cache (useful on logout)
   */
  clearCache(): void {
    this.cache.clear();
  }
}
```

---

### 2. Create the Secure Image Component

Create a reusable component for displaying authenticated images:

```bash
ng generate component components/secure-image
```

**File: `src/app/components/secure-image/secure-image.component.ts`**

```typescript
import { Component, Input, OnInit, OnDestroy } from '@angular/core';
import { SecureImageService } from '../../services/secure-image.service';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

@Component({
  selector: 'app-secure-image',
  templateUrl: './secure-image.component.html',
  styleUrls: ['./secure-image.component.css']
})
export class SecureImageComponent implements OnInit, OnDestroy {
  @Input() imageType!: string;  // e.g., 'practitioners_images', 'applications', 'payments'
  @Input() filename!: string;   // e.g., '1762017079_f95807b21b7ffe5dc134.jpg'
  @Input() alt: string = 'Image';
  @Input() cssClass: string = '';
  @Input() width?: string;
  @Input() height?: string;
  @Input() fallbackImage?: string; // Optional fallback image on error

  imageUrl: string | null = null;
  loading: boolean = true;
  error: boolean = false;

  private destroy$ = new Subject<void>();

  constructor(private secureImageService: SecureImageService) {}

  ngOnInit(): void {
    if (!this.imageType || !this.filename) {
      console.error('SecureImageComponent: imageType and filename are required');
      this.error = true;
      this.loading = false;
      return;
    }

    this.loadImage();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private loadImage(): void {
    this.secureImageService.getSignedUrl(this.imageType, this.filename)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (url) => {
          if (url) {
            this.imageUrl = url;
            this.error = false;
          } else {
            this.error = true;
            this.imageUrl = this.fallbackImage || null;
          }
          this.loading = false;
        },
        error: (err) => {
          console.error('Error loading secure image:', err);
          this.error = true;
          this.imageUrl = this.fallbackImage || null;
          this.loading = false;
        }
      });
  }

  onImageError(): void {
    this.error = true;
    if (this.fallbackImage) {
      this.imageUrl = this.fallbackImage;
    }
  }
}
```

**File: `src/app/components/secure-image/secure-image.component.html`**

```html
<div class="secure-image-container" [ngClass]="cssClass">
  <!-- Loading state -->
  <div *ngIf="loading" class="loading-placeholder">
    <div class="spinner"></div>
    <span>Loading...</span>
  </div>

  <!-- Image loaded -->
  <img
    *ngIf="!loading && imageUrl"
    [src]="imageUrl"
    [alt]="alt"
    [width]="width"
    [height]="height"
    [class]="cssClass"
    (error)="onImageError()"
  />

  <!-- Error state -->
  <div *ngIf="!loading && error && !fallbackImage" class="error-placeholder">
    <span>Failed to load image</span>
  </div>
</div>
```

**File: `src/app/components/secure-image/secure-image.component.css`**

```css
.secure-image-container {
  display: inline-block;
  position: relative;
}

.loading-placeholder,
.error-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100px;
  min-width: 100px;
  background-color: #f3f4f6;
  border-radius: 4px;
  color: #6b7280;
  font-size: 14px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 8px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-placeholder {
  background-color: #fee2e2;
  color: #991b1b;
}
```

---

### 3. Register in Module

**File: `src/app/app.module.ts`**

```typescript
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';

import { AppComponent } from './app.component';
import { SecureImageComponent } from './components/secure-image/secure-image.component';
import { SecureImageService } from './services/secure-image.service';

@NgModule({
  declarations: [
    AppComponent,
    SecureImageComponent
  ],
  imports: [
    BrowserModule,
    HttpClientModule
  ],
  providers: [SecureImageService],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

---

## Usage Examples

### Basic Usage

```html
<app-secure-image
  imageType="practitioners_images"
  filename="1762017079_f95807b21b7ffe5dc134.jpg"
  alt="Practitioner Photo"
  cssClass="rounded-full w-32 h-32"
>
</app-secure-image>
```

### With Dynamic Data from API

```typescript
// Component
export class PractitionerListComponent implements OnInit {
  practitioners: any[] = [];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.http.get<any[]>('/api/practitioners').subscribe(data => {
      this.practitioners = data;
    });
  }
}
```

```html
<!-- Template -->
<div *ngFor="let practitioner of practitioners" class="practitioner-card">
  <app-secure-image
    imageType="practitioners_images"
    [filename]="practitioner.picture"
    [alt]="practitioner.name"
    cssClass="practitioner-photo"
  >
  </app-secure-image>
  <h3>{{ practitioner.name }}</h3>
  <p>{{ practitioner.specialty }}</p>
</div>
```

### With Fallback Image

```html
<app-secure-image
  imageType="applications"
  [filename]="application.documentPath"
  alt="Application Document"
  cssClass="document-preview"
  fallbackImage="/assets/images/no-document.png"
>
</app-secure-image>
```

### With Custom Dimensions

```html
<app-secure-image
  imageType="payments"
  [filename]="invoice.receiptImage"
  alt="Payment Receipt"
  width="400"
  height="300"
  cssClass="receipt-image"
>
</app-secure-image>
```

---

## Advanced Usage

### 1. Download Image with Auth

Create a service method to trigger downloads:

**Add to `secure-image.service.ts`:**

```typescript
/**
 * Download file with authentication
 */
downloadFile(imageType: string, filename: string): void {
  this.getSignedUrl(imageType, filename).subscribe(signedUrl => {
    if (!signedUrl) {
      console.error('Failed to generate download URL');
      return;
    }

    // Add download parameter
    const downloadUrl = `${signedUrl}&download=1`;

    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    link.click();
  });
}
```

**Usage in component:**

```typescript
export class DocumentViewComponent {
  constructor(private secureImageService: SecureImageService) {}

  downloadDocument(type: string, filename: string): void {
    this.secureImageService.downloadFile(type, filename);
  }
}
```

```html
<button (click)="downloadDocument('applications', document.filename)">
  Download Document
</button>
```

---

### 2. Preload Multiple Images

**Add to `secure-image.service.ts`:**

```typescript
/**
 * Preload multiple images to cache
 */
preloadImages(images: Array<{type: string, filename: string}>): Observable<string[]> {
  const requests = images.map(img => this.getSignedUrl(img.type, img.filename));
  return forkJoin(requests);
}
```

**Usage:**

```typescript
import { forkJoin } from 'rxjs';

export class GalleryComponent implements OnInit {
  constructor(private secureImageService: SecureImageService) {}

  ngOnInit(): void {
    const images = [
      { type: 'practitioners_images', filename: 'photo1.jpg' },
      { type: 'practitioners_images', filename: 'photo2.jpg' },
      { type: 'practitioners_images', filename: 'photo3.jpg' }
    ];

    // Preload all images
    this.secureImageService.preloadImages(images).subscribe();
  }
}
```

---

### 3. Clear Cache on Logout

**In your auth service:**

```typescript
import { SecureImageService } from './secure-image.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  constructor(private secureImageService: SecureImageService) {}

  logout(): void {
    localStorage.removeItem('auth_token');
    this.secureImageService.clearCache(); // Clear image cache
    // ... rest of logout logic
  }
}
```

---

### 4. Lazy Loading with Intersection Observer

**Create directive: `ng generate directive directives/lazy-load-image`**

```typescript
import { Directive, ElementRef, Input, OnInit } from '@angular/core';

@Directive({
  selector: '[appLazyLoadImage]'
})
export class LazyLoadImageDirective implements OnInit {
  @Input() appLazyLoadImage!: string;

  constructor(private el: ElementRef) {}

  ngOnInit(): void {
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target as HTMLImageElement;
            img.src = this.appLazyLoadImage;
            observer.unobserve(img);
          }
        });
      });

      observer.observe(this.el.nativeElement);
    } else {
      // Fallback for browsers without IntersectionObserver
      this.el.nativeElement.src = this.appLazyLoadImage;
    }
  }
}
```

**Usage:**

```html
<img [appLazyLoadImage]="signedImageUrl" alt="Lazy loaded image" />
```

---

## Migration from Direct URLs

### Before (Insecure):

```typescript
export class PractitionerComponent {
  practitioner = {
    name: 'Dr. Smith',
    picture: 'http://localhost:8080/file-server/image-render/practitioners_images/photo.jpg'
  };
}
```

```html
<img [src]="practitioner.picture" [alt]="practitioner.name" />
```

### After (Secure):

**Update your backend API response:**

```php
// Backend: Return relative path instead of full URL
return [
    'name' => 'Dr. Smith',
    'picture' => 'photo.jpg',
    'picture_type' => 'practitioners_images'
];
```

**Update your Angular component:**

```typescript
export class PractitionerComponent {
  practitioner = {
    name: 'Dr. Smith',
    picture: 'photo.jpg',
    picture_type: 'practitioners_images'
  };
}
```

```html
<app-secure-image
  [imageType]="practitioner.picture_type"
  [filename]="practitioner.picture"
  [alt]="practitioner.name"
>
</app-secure-image>
```

---

## Configuration

### Environment-based API URL

**File: `src/environments/environment.ts`**

```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8080'
};
```

**File: `src/environments/environment.prod.ts`**

```typescript
export const environment = {
  production: true,
  apiUrl: 'https://api.yourproduction.com'
};
```

**Update service:**

```typescript
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class SecureImageService {
  private baseUrl = `${environment.apiUrl}/file-server`;
  // ...
}
```

---

## Troubleshooting

### 1. CORS Issues

If you get CORS errors, ensure your backend allows the frontend origin:

**Backend (CodeIgniter): `app/Config/Filters.php`**

```php
public $globals = [
    'before' => [
        'cors' => ['except' => []],
    ],
];
```

### 2. Token Not Found

Ensure your interceptor adds the auth token:

**Create: `src/app/interceptors/auth.interceptor.ts`**

```typescript
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const token = localStorage.getItem('auth_token');

    if (token) {
      req = req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
      });
    }

    return next.handle(req);
  }
}
```

**Register in `app.module.ts`:**

```typescript
import { HTTP_INTERCEPTORS } from '@angular/common/http';
import { AuthInterceptor } from './interceptors/auth.interceptor';

@NgModule({
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }
  ]
})
export class AppModule { }
```

### 3. Images Not Loading

1. Check browser console for errors
2. Verify the API endpoint is correct
3. Ensure token is valid and not expired
4. Check network tab for the actual requests

---

## Performance Best Practices

1. **Use Caching**: The service automatically caches signed URLs
2. **Preload Critical Images**: Use `preloadImages()` for above-the-fold images
3. **Lazy Load**: Use Intersection Observer for below-the-fold images
4. **Clear Cache on Logout**: Prevent stale URLs after re-authentication
5. **Use OnPush Change Detection**: For better performance in large lists

```typescript
@Component({
  selector: 'app-practitioner-list',
  templateUrl: './practitioner-list.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class PractitionerListComponent {
  // ...
}
```

---

## Complete Example

Here's a complete example of a practitioner profile component:

**practitioner-profile.component.ts:**

```typescript
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';

interface Practitioner {
  id: string;
  name: string;
  specialty: string;
  email: string;
  phone: string;
  picture: string;
  picture_type: string;
  license_document: string;
  license_document_type: string;
}

@Component({
  selector: 'app-practitioner-profile',
  templateUrl: './practitioner-profile.component.html',
  styleUrls: ['./practitioner-profile.component.css']
})
export class PractitionerProfileComponent implements OnInit {
  practitioner: Practitioner | null = null;
  loading = true;

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    this.http.get<Practitioner>(`/api/practitioners/${id}`).subscribe({
      next: (data) => {
        this.practitioner = data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading practitioner:', err);
        this.loading = false;
      }
    });
  }
}
```

**practitioner-profile.component.html:**

```html
<div class="profile-container" *ngIf="!loading && practitioner">
  <div class="profile-header">
    <app-secure-image
      [imageType]="practitioner.picture_type"
      [filename]="practitioner.picture"
      [alt]="practitioner.name"
      cssClass="profile-photo"
      fallbackImage="/assets/images/default-avatar.png"
    >
    </app-secure-image>

    <div class="profile-info">
      <h1>{{ practitioner.name }}</h1>
      <p class="specialty">{{ practitioner.specialty }}</p>
      <p class="contact">{{ practitioner.email }} | {{ practitioner.phone }}</p>
    </div>
  </div>

  <div class="documents-section">
    <h2>License Document</h2>
    <app-secure-image
      [imageType]="practitioner.license_document_type"
      [filename]="practitioner.license_document"
      alt="License Document"
      cssClass="document-preview"
    >
    </app-secure-image>
  </div>
</div>

<div class="loading" *ngIf="loading">
  <p>Loading profile...</p>
</div>
```

This implementation is production-ready and follows Angular 15 best practices!
