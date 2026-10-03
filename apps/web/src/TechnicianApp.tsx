import React, { Suspense, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './lib/query-client';
import { NetworkStatusProvider } from './providers/NetworkStatusProvider';
import { ToastProvider } from './providers/ToastProvider';
import { ErrorBoundary } from './components/common/ErrorBoundary';
import { LoadingState } from './components/ui/LoadingState';
import { PwaCoordinator } from './lib/pwa-coordinator';
import { TechnicianPortalRouter } from './modules/technician-portal/routes';
import { notifyCrmReady, updateSplashProgress } from './lib/splashScreen';

export function TechnicianSplashScreenCoordinator() {
  useEffect(() => {
    updateSplashProgress(75);
    notifyCrmReady();
  }, []);

  return null;
}

export function TechnicianApp() {
  return (
    <QueryClientProvider client={queryClient}>
      <ToastProvider>
        <NetworkStatusProvider>
          <TechnicianSplashScreenCoordinator />
          <ErrorBoundary>
            <BrowserRouter>
              <PwaCoordinator>
                <Suspense
                  fallback={
                    <div className="min-h-screen bg-workspace flex items-center justify-center text-slate-500">
                      <LoadingState message="Loading Technician Portal..." />
                    </div>
                  }
                >
                  <Routes>
                    {/* Root redirection to primary portal entry */}
                    <Route path="/" element={<Navigate to="/technician" replace />} />

                    {/* Standalone origin convenience aliases */}
                    <Route path="/login" element={<Navigate to="/technician/login" replace />} />
                    <Route path="/services" element={<Navigate to="/technician/services" replace />} />
                    <Route path="/services/:id" element={<Navigate to="/technician/services" replace />} />
                    <Route path="/completed-services" element={<Navigate to="/technician/completed-services" replace />} />
                    <Route path="/profile" element={<Navigate to="/technician/profile" replace />} />
                    <Route path="/notifications" element={<Navigate to="/technician/notifications" replace />} />

                    {/* Canonical Technician Portal Route Tree */}
                    <Route path="/technician/*" element={<TechnicianPortalRouter />} />

                    {/* Universal fallback */}
                    <Route path="*" element={<Navigate to="/technician" replace />} />
                  </Routes>
                </Suspense>
              </PwaCoordinator>
            </BrowserRouter>
          </ErrorBoundary>
        </NetworkStatusProvider>
      </ToastProvider>
    </QueryClientProvider>
  );
}

export default TechnicianApp;
