<?php

require_once __DIR__ . '/../Core/Controller.php';

class PublicController extends Controller
{
    public function home(): void
    {
        $this->view('public/home', [
            'title' => 'Home',
            'heading' => 'Mate Tournaments',
        ]);
    }

    public function events(): void
    {
        $this->view('public/events', [
            'title' => 'Events',
            'heading' => 'Upcoming Events',
        ]);
    }

    public function about(): void
    {
        $this->view('public/about', [
            'title' => 'About',
            'heading' => 'About Mate Tournaments',
        ]);
    }

    public function contact(): void
    {
        $this->view('public/contact', [
            'title' => 'Contact',
            'heading' => 'Contact Mate Tournaments',
        ]);
    }
}