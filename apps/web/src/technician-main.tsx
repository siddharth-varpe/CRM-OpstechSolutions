import React from 'react';
import ReactDOM from 'react-dom/client';
import { TechnicianApp } from './TechnicianApp';
import './styles/index.css';

const rootElement = document.getElementById('root');

if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <TechnicianApp />
    </React.StrictMode>
  );
}
