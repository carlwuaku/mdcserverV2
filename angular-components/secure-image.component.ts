import { Component, Input, OnInit, OnDestroy } from '@angular/core';
import { SecureImageService } from '../services/secure-image.service';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

/**
 * SecureImageComponent - Display images that require authentication
 *
 * Usage:
 * <app-secure-image
 *   imageType="practitioners_images"
 *   filename="photo.jpg"
 *   alt="Practitioner Photo"
 *   cssClass="rounded-image"
 *   [width]="200"
 *   [height]="200"
 *   fallbackImage="/assets/default-avatar.png"
 * ></app-secure-image>
 */
@Component({
  selector: 'app-secure-image',
  templateUrl: './secure-image.component.html',
  styleUrls: ['./secure-image.component.css']
})
export class SecureImageComponent implements OnInit, OnDestroy {
  /**
   * Type of image - must match backend file types
   * Options: 'practitioners_images', 'documents', 'applications', 'payments', 'qr_codes'
   */
  @Input() imageType!: string;

  /**
   * Filename (e.g., '1762017079_f95807b21b7ffe5dc134.jpg')
   */
  @Input() filename!: string;

  /**
   * Alt text for the image
   */
  @Input() alt: string = 'Image';

  /**
   * CSS class to apply to the image
   */
  @Input() cssClass: string = '';

  /**
   * Image width (optional)
   */
  @Input() width?: string | number;

  /**
   * Image height (optional)
   */
  @Input() height?: string | number;

  /**
   * Fallback image to show on error
   */
  @Input() fallbackImage?: string;

  /**
   * Show loading spinner
   */
  @Input() showLoading: boolean = true;

  /**
   * Show error message
   */
  @Input() showError: boolean = true;

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
    this.loading = true;
    this.error = false;

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

  /**
   * Handle image load error
   */
  onImageError(): void {
    this.error = true;
    if (this.fallbackImage && this.imageUrl !== this.fallbackImage) {
      this.imageUrl = this.fallbackImage;
    }
  }

  /**
   * Retry loading the image
   */
  retry(): void {
    this.loadImage();
  }
}
