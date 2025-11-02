# Angular 15 Secure Image Components

Ready-to-use Angular 15 components for displaying authenticated images.

## Installation

### Step 1: Copy Files to Your Project

Copy these files to your Angular project:

```
src/app/
├── services/
│   └── secure-image.service.ts
└── components/
    └── secure-image/
        ├── secure-image.component.ts
        ├── secure-image.component.html
        └── secure-image.component.css
```

### Step 2: Update Module

Add the component and service to your module:

**File: `src/app/app.module.ts` (or your feature module)**

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
    SecureImageComponent  // Add this
  ],
  imports: [
    BrowserModule,
    HttpClientModule  // Required for HTTP requests
  ],
  providers: [
    SecureImageService  // Add this
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

### Step 3: Configure API URL

Update the base URL in the service to match your backend:

**File: `src/app/services/secure-image.service.ts`**

```typescript
private baseUrl = 'http://localhost:8080/file-server'; // Change this
```

Or better yet, use environment variables:

**File: `src/environments/environment.ts`**

```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8080'
};
```

**Then update the service:**

```typescript
import { environment } from '../../environments/environment';

private baseUrl = `${environment.apiUrl}/file-server`;
```

## Usage

### Basic Example

```html
<app-secure-image
  imageType="practitioners_images"
  filename="photo.jpg"
  alt="Practitioner Photo"
>
</app-secure-image>
```

### With Dynamic Data

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
<div *ngFor="let practitioner of practitioners">
  <app-secure-image
    imageType="practitioners_images"
    [filename]="practitioner.picture"
    [alt]="practitioner.name"
    cssClass="profile-photo"
  >
  </app-secure-image>
</div>
```

### All Available Options

```html
<app-secure-image
  imageType="applications"
  filename="document.jpg"
  alt="Application Document"
  cssClass="rounded shadow-lg"
  [width]="300"
  [height]="200"
  fallbackImage="/assets/default-image.png"
  [showLoading]="true"
  [showError]="true"
>
</app-secure-image>
```

## Image Types

The `imageType` input must match one of these backend types:

- `practitioners_images` - Practitioner photos
- `documents` - General documents
- `applications` - Application-related files
- `payments` - Payment receipts
- `qr_codes` - QR code images

## API Reference

### SecureImageComponent

**Inputs:**

| Input | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `imageType` | `string` | Yes | - | Type of image (see Image Types above) |
| `filename` | `string` | Yes | - | Name of the file |
| `alt` | `string` | No | `'Image'` | Alt text for the image |
| `cssClass` | `string` | No | `''` | CSS class to apply |
| `width` | `string \| number` | No | - | Image width |
| `height` | `string \| number` | No | - | Image height |
| `fallbackImage` | `string` | No | - | Fallback image on error |
| `showLoading` | `boolean` | No | `true` | Show loading spinner |
| `showError` | `boolean` | No | `true` | Show error message |

### SecureImageService

**Methods:**

```typescript
// Get signed URL for an image
getSignedUrl(imageType: string, filename: string): Observable<string>

// Get image as blob URL (alternative method)
getImageAsBlob(imageUrl: string): Observable<string>

// Download file with authentication
downloadFile(imageType: string, filename: string): void

// Clear cached URLs (call on logout)
clearCache(): void

// Set base URL programmatically
setBaseUrl(url: string): void
```

## Advanced Examples

### Download Button

```typescript
export class DocumentViewComponent {
  constructor(private secureImageService: SecureImageService) {}

  downloadDocument(type: string, filename: string): void {
    this.secureImageService.downloadFile(type, filename);
  }
}
```

```html
<app-secure-image
  imageType="applications"
  [filename]="document.filename"
  alt="Application Document"
>
</app-secure-image>

<button (click)="downloadDocument('applications', document.filename)">
  Download
</button>
```

### Clear Cache on Logout

```typescript
import { SecureImageService } from './services/secure-image.service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  constructor(private secureImageService: SecureImageService) {}

  logout(): void {
    localStorage.removeItem('auth_token');
    this.secureImageService.clearCache(); // Clear image cache
    this.router.navigate(['/login']);
  }
}
```

### Custom Styling Examples

```css
/* Circular profile photo */
.profile-photo {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #3b82f6;
}

/* Document thumbnail */
.document-thumbnail {
  width: 200px;
  height: 250px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Full width responsive image */
.responsive-image {
  width: 100%;
  height: auto;
  max-width: 800px;
}
```

## Troubleshooting

### Images not loading

1. **Check the console for errors**
   - Open browser DevTools (F12) → Console tab
   - Look for errors related to authentication or network

2. **Verify JWT token exists**
   ```javascript
   console.log(localStorage.getItem('auth_token'));
   ```

3. **Check network tab**
   - Open DevTools → Network tab
   - Look for the signed URL request
   - Check if it returns 200 OK or an error

4. **Verify API URL is correct**
   - Check `baseUrl` in `secure-image.service.ts`
   - Make sure it matches your backend URL

### CORS errors

If you see CORS errors in console, ensure your backend allows requests from your frontend origin.

### Token not being sent

If the Authorization header is not being sent:

1. Create an HTTP interceptor:

```typescript
// src/app/interceptors/auth.interceptor.ts
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const token = localStorage.getItem('auth_token');

    if (token) {
      req = req.clone({
        setHeaders: { Authorization: `Bearer ${token}` }
      });
    }

    return next.handle(req);
  }
}
```

2. Register in `app.module.ts`:

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

## Performance Tips

1. **Caching**: The service automatically caches signed URLs for ~55 minutes
2. **Lazy Loading**: Use `*ngIf` to delay loading off-screen images
3. **Fallback Images**: Provide fallback images to improve UX on errors
4. **OnPush Change Detection**: Use for components with many images

```typescript
@Component({
  selector: 'app-image-gallery',
  templateUrl: './image-gallery.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class ImageGalleryComponent {}
```

## Complete Example

See `ANGULAR_15_IMAGE_AUTH.md` for a complete practitioner profile example.

## Support

For issues or questions:
- Check the comprehensive guide: `ANGULAR_15_IMAGE_AUTH.md`
- Review the backend implementation: `app/Controllers/AssetController.php`
- Check CodeIgniter routes: `app/Config/Routes.php`
