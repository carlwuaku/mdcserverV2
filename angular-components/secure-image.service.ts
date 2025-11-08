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
   * @param imageType - Type of image (practitioners_images, applications, payments, documents, qr_codes)
   * @param filename - Name of the file
   * @returns Observable<string> - The signed URL
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
   * @param imageUrl - Full URL of the image
   * @returns Observable<string> - Blob URL
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
   * Download file with authentication
   * @param imageType - Type of image
   * @param filename - Name of the file
   */
  downloadFile(imageType: string, filename: string): void {
    this.getSignedUrl(imageType, filename).subscribe(signedUrl => {
      if (!signedUrl) {
        console.error('Failed to generate download URL');
        return;
      }

      // Add download parameter
      const downloadUrl = signedUrl.includes('?')
        ? `${signedUrl}&download=1`
        : `${signedUrl}?download=1`;

      // Create temporary link and trigger download
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  /**
   * Clear cache (useful on logout)
   */
  clearCache(): void {
    this.cache.clear();
  }

  /**
   * Set base URL (useful for environment-specific configuration)
   * @param url - Base URL for the file server API
   */
  setBaseUrl(url: string): void {
    this.baseUrl = url;
  }
}
