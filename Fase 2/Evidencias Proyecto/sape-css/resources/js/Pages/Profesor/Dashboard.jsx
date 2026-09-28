import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function ProfesorDashboard({ auth, userName }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Panel Docente e IA Pedagógica</h2>}
        >
            <Head title="Profesor Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-4">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-bold">¡Bienvenido Profesor, {userName}!</h3>
                            <p className="mt-2 text-gray-600">Este es tu centro de operaciones. Aquí estarán el Copiloto de IA y el redactor de informes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}