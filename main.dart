import 'package:flutter/material.dart';

void main() {
  runApp(
    MyApp(),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: SafeArea(
        child: Scaffold(
          backgroundColor: Colors.red,
          body: Column(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              Container(
                margin: EdgeInsets.only(left:40, top: 20, bottom: 20),
                padding: EdgeInsets.all(20),
                height: 150,
                width: double.infinity,
                color: Colors.yellow,
                child: Center(child: Text("Ayam Geprek Pak Hambali"))
              ),
              SizedBox(width: 20),
              Container(
                margin: EdgeInsets.only(left:40, top: 20, bottom: 20),
                padding: EdgeInsets.all(20),
                height: 150,
                width: double.infinity,
                color: Colors.yellow,
                child: Center(child: Text("Mie Ayam Mas Amba"))
              ),
              Container(
                margin: EdgeInsets.only(left:40, top: 20, bottom: 20),
                padding: EdgeInsets.all(20),
                height: 150,
                width: double.infinity,
                color: Colors.yellow,
                child: Center(child: Text("Apel Hijau Mas Eja"))
              ),
              Container(
                margin: EdgeInsets.only(left:40, top: 20, bottom: 20, right: 20),
                padding: EdgeInsets.all(20),
                height: 150,
                width: double.infinity,
                color: Colors.yellow,
                child: Center(child: Text("Es Teh Manis Mas Eja"))
              ),
            ],
          ),
        ),
      ),
    );
  }
}
